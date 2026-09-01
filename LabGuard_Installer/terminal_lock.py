import os
import sys
import time
import signal
import atexit
import threading
import subprocess
import re
import tempfile
import urllib3
import urllib.parse
import tkinter as tk
from tkinter import ttk, messagebox
import requests
import json

import ctypes
from ctypes import wintypes

# Disable SSL warnings for local development (.test domains / IP endpoints)
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# =====================================================================
# 1. WIN32 API CONSTANTS & STRUCTURES (64-BIT SAFE CTYPES)
# =====================================================================
WH_KEYBOARD_LL = 13
VK_TAB = 0x09
VK_ESCAPE = 0x1B
VK_CONTROL = 0x11
VK_MENU = 0x12  # Alt key
VK_LWIN = 0x5B  # Left Win key
VK_RWIN = 0x5C  # Right Win key
VK_F4 = 0x73  # F4 key

ULONG_PTR = (
    ctypes.c_ulonglong if ctypes.sizeof(ctypes.c_void_p) == 8 else ctypes.c_ulong
)
LRESULT = ctypes.c_ssize_t


class KBDLLHOOKSTRUCT(ctypes.Structure):
    _fields_ = [
        ("vkCode", wintypes.DWORD),
        ("scanCode", wintypes.DWORD),
        ("flags", wintypes.DWORD),
        ("time", wintypes.DWORD),
        ("dwExtraInfo", ULONG_PTR),
    ]


_hook_id = None
_hook_proc_ref = None  # Prevents Python's garbage collector from dropping the callback

# Define Win32 C Function Signatures to Prevent 64-bit Integer Overflow
user32 = ctypes.windll.user32
kernel32 = ctypes.windll.kernel32

HOOKPROC = ctypes.WINFUNCTYPE(LRESULT, ctypes.c_int, wintypes.WPARAM, wintypes.LPARAM)

user32.CallNextHookEx.argtypes = [
    wintypes.HANDLE,
    ctypes.c_int,
    wintypes.WPARAM,
    wintypes.LPARAM,
]
user32.CallNextHookEx.restype = LRESULT

user32.SetWindowsHookExW.argtypes = [
    ctypes.c_int,
    HOOKPROC,
    wintypes.HINSTANCE,
    wintypes.DWORD,
]
user32.SetWindowsHookExW.restype = wintypes.HANDLE

user32.UnhookWindowsHookEx.argtypes = [wintypes.HANDLE]
user32.UnhookWindowsHookEx.restype = wintypes.BOOL


# =====================================================================
# 2. CTYPES FUNCTIONS: TASKBAR CONTROL
# =====================================================================
def hide_taskbar():
    """Hides primary and secondary display taskbars."""
    try:
        hwnd_primary = user32.FindWindowW("Shell_TrayWnd", None)
        if hwnd_primary:
            user32.ShowWindow(hwnd_primary, 0)  # 0 = SW_HIDE
            user32.EnableWindow(hwnd_primary, False)

        hwnd_secondary = user32.FindWindowW("Shell_SecondaryTrayWnd", None)
        if hwnd_secondary:
            user32.ShowWindow(hwnd_secondary, 0)
            user32.EnableWindow(hwnd_secondary, False)
        print("[DEBUG] Taskbar hidden.")
    except Exception as e:
        print(f"[DEBUG] Error hiding taskbar: {e}")


def show_taskbar():
    """Restores primary and secondary display taskbars."""
    try:
        hwnd_primary = user32.FindWindowW("Shell_TrayWnd", None)
        if hwnd_primary:
            user32.EnableWindow(hwnd_primary, True)
            user32.ShowWindow(hwnd_primary, 5)  # 5 = SW_SHOW

        hwnd_secondary = user32.FindWindowW("Shell_SecondaryTrayWnd", None)
        if hwnd_secondary:
            user32.EnableWindow(hwnd_secondary, True)
            user32.ShowWindow(hwnd_secondary, 5)
        print("[DEBUG] Taskbar restored.")
    except Exception as e:
        print(f"[DEBUG] Error showing taskbar: {e}")


# =====================================================================
# 3. HIGH-SPEED LOW-LEVEL KEYBOARD HOOK (64-BIT SAFE FAST PATH)
# =====================================================================
def _low_level_keyboard_proc(nCode, wParam, lParam):
    """Intercepts keypresses with zero-latency fast path for normal typing."""
    try:
        if nCode >= 0 and lParam:
            kb_struct = KBDLLHOOKSTRUCT.from_address(lParam)
            vk_code = kb_struct.vkCode

            # FAST PATH: Immediately pass standard typing keys (0-9, A-Z, Backspace, Enter, Space)
            if vk_code not in (VK_LWIN, VK_RWIN, VK_TAB, VK_ESCAPE, VK_F4):
                return user32.CallNextHookEx(_hook_id, nCode, wParam, lParam)

            # SLOW PATH: Only run system key modifier checks if a system key was pressed
            flags = kb_struct.flags
            is_alt_pressed = (
                bool(flags & 0x20) or (user32.GetAsyncKeyState(VK_MENU) & 0x8000) != 0
            )

            # 1. Block Left & Right Windows Keys
            if vk_code in (VK_LWIN, VK_RWIN):
                return 1

            # 2. Block Alt + Tab
            if vk_code == VK_TAB and is_alt_pressed:
                return 1

            # 3. Block Alt + Esc
            if vk_code == VK_ESCAPE and is_alt_pressed:
                return 1

            # 4. Block Ctrl + Esc (Start Menu) & Ctrl + Shift + Esc (Task Manager)
            if vk_code == VK_ESCAPE:
                is_ctrl_pressed = (user32.GetAsyncKeyState(VK_CONTROL) & 0x8000) != 0
                if is_ctrl_pressed:
                    return 1

            # 5. Block Alt + F4
            if vk_code == VK_F4 and is_alt_pressed:
                return 1

    except Exception as e:
        print(f"[DEBUG] Hook Procedure Error: {e}")

    return user32.CallNextHookEx(_hook_id, nCode, wParam, lParam)


def start_keyboard_hook():
    """Installs the low-level keyboard hook."""
    global _hook_id, _hook_proc_ref
    if _hook_id is not None:
        return

    try:
        _hook_proc_ref = HOOKPROC(_low_level_keyboard_proc)

        # Passing None for hMod fixes Error 126 in Python
        _hook_id = user32.SetWindowsHookExW(WH_KEYBOARD_LL, _hook_proc_ref, None, 0)
        if not _hook_id or _hook_id == 0:
            err = kernel32.GetLastError()
            print(f"[DEBUG] SetWindowsHookExW FAILED with error code: {err}")
            _hook_id = None
        else:
            print(f"[DEBUG] Keyboard Hook successfully installed! Hook ID: {_hook_id}")
    except Exception as e:
        print(f"[DEBUG] Keyboard Hook installation exception: {e}")


def stop_keyboard_hook():
    """Unhooks the low-level keyboard listener."""
    global _hook_id
    if _hook_id:
        try:
            user32.UnhookWindowsHookEx(_hook_id)
            print("[DEBUG] Keyboard Hook uninstalled.")
        except Exception as e:
            print(f"[DEBUG] Error uninstalling hook: {e}")
        _hook_id = None


# =====================================================================
# 4. CONFIGURATION & SANCTUM CSRF SESSION MANAGER
# =====================================================================
def load_config():
    """Loads configuration dynamically from config.json beside the script/exe."""
    if getattr(sys, "frozen", False):
        base_dir = os.path.dirname(sys.executable)
    else:
        base_dir = os.path.dirname(os.path.abspath(__file__))

    config_path = os.path.join(base_dir, "config.json")

    config_data = {
        "server_url": "https://labguard.it.com/api/pc",
        "lab": "LAB 1",
        "pc": "PC-01",
    }

    if os.path.exists(config_path):
        try:
            with open(config_path, "r", encoding="utf-8") as f:
                config_data = json.load(f)
        except Exception as e:
            print(f"Error loading config.json: {e}")

    return config_data


# --- CONFIGURATION ---
config = load_config()

API_URL = config.get("server_url", "https://labguard.it.com/api/pc").rstrip('/')
if not API_URL.endswith('/api/pc'):
    API_URL += '/api/pc'

LAB_ID = str(config.get("lab", "LAB 1")).strip()
PC_NUMBER = str(config.get("pc", "PC-01")).strip()
HEADERS = {"Accept": "application/json"}
# ---------------------

def get_authenticated_session():
    """
    Creates a requests.Session, fetches Laravel Sanctum CSRF cookies,
    and attaches the X-XSRF-TOKEN header to prevent HTTP 419 errors.
    """
    session = requests.Session()
    session.verify = False

    # Extract base domain (e.g., https://labguard.it.com)
    base_domain = API_URL.split("/api")[0]

    try:
        # Fetch CSRF cookie from Laravel
        session.get(f"{base_domain}/sanctum/csrf-cookie", headers=HEADERS, timeout=5)

        # Extract XSRF-TOKEN cookie
        csrf_token = session.cookies.get("XSRF-TOKEN")
        if csrf_token:
            session.headers.update({
                "X-XSRF-TOKEN": urllib.parse.unquote(csrf_token),
                "Accept": "application/json"
            })
    except Exception as e:
        print(f"[DEBUG] CSRF Cookie Fetch Exception: {e}")

    return session


def cleanup_security():
    """Failsafe: Always called on exit or crash to restore system usability."""
    stop_keyboard_hook()
    show_taskbar()


def send_logout_signal():
    """Tells Laravel to release this PC and set time_out."""
    cleanup_security()
    try:
        session = get_authenticated_session()
        session.post(
            f"{API_URL}/logout",
            json={"pc_number": PC_NUMBER},
            timeout=3,
        )
        print(f"Signal Sent: {PC_NUMBER} has been released.")
    except Exception as e:
        print(f"Logout signal failed: {e}")


def handle_exit_signal(sig, frame):
    """Handles OS-level termination signals."""
    print("Force shutdown detected...")
    send_logout_signal()
    sys.exit(0)


# Main thread signal registrations
signal.signal(signal.SIGINT, handle_exit_signal)
signal.signal(signal.SIGTERM, handle_exit_signal)
atexit.register(cleanup_security)


def register_shutdown_hooks():
    """Registers Windows kernel shutdown handlers (safe for background execution)."""

    def windows_shutdown_handler(ctrl_type):
        if ctrl_type in (2, 5, 6):
            print("[SHUTDOWN] Windows OS Shutdown/Logoff detected! Releasing PC...")
            send_logout_signal()
            return True
        return False

    try:
        PHANDLER_ROUTINE = ctypes.WINFUNCTYPE(wintypes.BOOL, wintypes.DWORD)
        handler_delegate = PHANDLER_ROUTINE(windows_shutdown_handler)
        register_shutdown_hooks.handler_delegate = handler_delegate
        kernel32.SetConsoleCtrlHandler(handler_delegate, True)
    except Exception as e:
        print(f"Could not register Windows shutdown hook: {e}")


# =====================================================================
# 5. WI-FI MANAGEMENT SERVICES (NATIVE C WIN32 API)
# =====================================================================
def turn_on_wifi_radio_native():
    """Uses Native C WlanSetInterface in wlanapi.dll to force software radio ON."""
    try:
        wlanapi = ctypes.windll.wlanapi

        class GUID(ctypes.Structure):
            _fields_ = [
                ("Data1", wintypes.DWORD),
                ("Data2", wintypes.WORD),
                ("Data3", wintypes.WORD),
                ("Data4", wintypes.BYTE * 8),
            ]

        class WLAN_INTERFACE_INFO(ctypes.Structure):
            _fields_ = [
                ("InterfaceGuid", GUID),
                ("strInterfaceDescription", wintypes.WCHAR * 256),
                ("isState", ctypes.c_uint),
            ]

        class WLAN_INTERFACE_INFO_LIST(ctypes.Structure):
            _fields_ = [
                ("dwNumberOfItems", wintypes.DWORD),
                ("dwIndex", wintypes.DWORD),
                ("InterfaceInfo", WLAN_INTERFACE_INFO * 1),
            ]

        class WLAN_PHY_RADIO_STATE(ctypes.Structure):
            _fields_ = [
                ("dwPhyIndex", wintypes.DWORD),
                ("dot11SoftwareRadioState", ctypes.c_uint),
                ("dot11HardwareRadioState", ctypes.c_uint),
            ]

        hClient = wintypes.HANDLE()
        pVersion = wintypes.DWORD()

        if (
            wlanapi.WlanOpenHandle(
                2, None, ctypes.byref(pVersion), ctypes.byref(hClient)
            )
            == 0
        ):
            pList = ctypes.POINTER(WLAN_INTERFACE_INFO_LIST)()

            if wlanapi.WlanEnumInterfaces(hClient, None, ctypes.byref(pList)) == 0:
                if pList.contents.dwNumberOfItems > 0:
                    guid = pList.contents.InterfaceInfo[0].InterfaceGuid

                    radio_state = WLAN_PHY_RADIO_STATE(0, 1, 1)

                    wlanapi.WlanSetInterface(
                        hClient,
                        ctypes.byref(guid),
                        4,
                        ctypes.sizeof(WLAN_PHY_RADIO_STATE),
                        ctypes.byref(radio_state),
                        None,
                    )

                    wlanapi.WlanScan(hClient, ctypes.byref(guid), None, None, None)

                wlanapi.WlanFreeMemory(pList)
            wlanapi.WlanCloseHandle(hClient, None)
            return True
    except Exception as e:
        print(f"Native radio activation failed: {e}")
    return False


def get_native_wifi_networks():
    """Queries wlanapi.dll directly to get available network SSIDs from memory."""
    ssids = []
    try:
        wlanapi = ctypes.windll.wlanapi

        class GUID(ctypes.Structure):
            _fields_ = [
                ("Data1", wintypes.DWORD),
                ("Data2", wintypes.WORD),
                ("Data3", wintypes.WORD),
                ("Data4", wintypes.BYTE * 8),
            ]

        class WLAN_INTERFACE_INFO(ctypes.Structure):
            _fields_ = [
                ("InterfaceGuid", GUID),
                ("strInterfaceDescription", wintypes.WCHAR * 256),
                ("isState", ctypes.c_uint),
            ]

        class WLAN_INTERFACE_INFO_LIST(ctypes.Structure):
            _fields_ = [
                ("dwNumberOfItems", wintypes.DWORD),
                ("dwIndex", wintypes.DWORD),
                ("InterfaceInfo", WLAN_INTERFACE_INFO * 1),
            ]

        class DOT11_SSID(ctypes.Structure):
            _fields_ = [("uSSIDLength", ctypes.c_ulong), ("ucSSID", ctypes.c_char * 32)]

        class WLAN_AVAILABLE_NETWORK(ctypes.Structure):
            _fields_ = [
                ("strProfileName", wintypes.WCHAR * 256),
                ("dot11Ssid", DOT11_SSID),
                ("dot11BssType", ctypes.c_uint),
                ("uNumberOfBssids", ctypes.c_ulong),
                ("bNetworkConnectable", wintypes.BOOL),
                ("wlanNotConnectableReason", wintypes.DWORD),
                ("uNumberOfPhyTypes", ctypes.c_ulong),
                ("dot11PhyTypes", ctypes.c_uint * 8),
                ("bMorePhyTypes", wintypes.BOOL),
                ("wlanSignalQuality", ctypes.c_ulong),
                ("bSecurityEnabled", wintypes.BOOL),
                ("dot11DefaultAuthAlgorithm", ctypes.c_uint),
                ("dot11DefaultCipherAlgorithm", ctypes.c_uint),
                ("dwFlags", wintypes.DWORD),
                ("dwReserved", wintypes.DWORD),
            ]

        class WLAN_AVAILABLE_NETWORK_LIST(ctypes.Structure):
            _fields_ = [
                ("dwNumberOfItems", wintypes.DWORD),
                ("dwIndex", wintypes.DWORD),
                ("Network", WLAN_AVAILABLE_NETWORK * 1),
            ]

        hClient = wintypes.HANDLE()
        pVersion = wintypes.DWORD()

        if (
            wlanapi.WlanOpenHandle(
                2, None, ctypes.byref(pVersion), ctypes.byref(hClient)
            )
            == 0
        ):
            pList = ctypes.POINTER(WLAN_INTERFACE_INFO_LIST)()

            if wlanapi.WlanEnumInterfaces(hClient, None, ctypes.byref(pList)) == 0:
                if pList.contents.dwNumberOfItems > 0:
                    guid = pList.contents.InterfaceInfo[0].InterfaceGuid
                    pNetList = ctypes.POINTER(WLAN_AVAILABLE_NETWORK_LIST)()

                    if (
                        wlanapi.WlanGetAvailableNetworkList(
                            hClient, ctypes.byref(guid), 2, None, ctypes.byref(pNetList)
                        )
                        == 0
                    ):
                        num_items = pNetList.contents.dwNumberOfItems
                        base_ptr = ctypes.addressof(pNetList.contents.Network)
                        stride = ctypes.sizeof(WLAN_AVAILABLE_NETWORK)

                        for i in range(num_items):
                            net = WLAN_AVAILABLE_NETWORK.from_address(
                                base_ptr + i * stride
                            )
                            ssid_len = net.dot11Ssid.uSSIDLength
                            if 0 < ssid_len <= 32:
                                ssid_bytes = bytes(net.dot11Ssid.ucSSID[:ssid_len])
                                ssid_str = ssid_bytes.decode(
                                    "utf-8", errors="ignore"
                                ).strip()
                                if ssid_str:
                                    ssids.append(ssid_str)

                        wlanapi.WlanFreeMemory(pNetList)
                wlanapi.WlanFreeMemory(pList)
            wlanapi.WlanCloseHandle(hClient, None)
    except Exception as e:
        print(f"Native network list failed: {e}")

    return sorted(list(set(ssids)))


def enable_wifi_adapter():
    """Enables network adapter and powers up software radio."""
    try:
        subprocess.run(
            [
                "powershell",
                "-NoProfile",
                "-Command",
                "Get-NetAdapter | Where-Object { $_.Name -match 'Wi-Fi|Wireless|WiFi' } | Enable-NetAdapter -Confirm:$false",
            ],
            capture_output=True,
            text=True,
            check=False,
        )
    except Exception:
        pass

    turn_on_wifi_radio_native()
    return True, "Wi-Fi enabled."


# =====================================================================
# 6. GLASSMORPHIC NOTIFICATION OVERLAY
# =====================================================================
class CinematicNotify(tk.Toplevel):
    """Custom glassmorphic notification overlay with Fade-Out."""

    def __init__(self, parent, title, message, color="#D4AF37"):
        super().__init__(parent)
        self.overrideredirect(True)
        self.attributes("-topmost", True)
        self.attributes("-alpha", 1.0)
        self.configure(bg="#1e293b", highlightbackground=color, highlightthickness=2)

        p_w = parent.winfo_screenwidth()
        p_h = parent.winfo_screenheight()

        width, height = 400, 160
        x = (p_w // 2) - (width // 2)
        y = (p_h // 2) - (height // 2)
        self.geometry(f"{width}x{height}+{x}+{y}")

        tk.Label(
            self, text=title.upper(), fg=color, bg="#1e293b", font=("Arial Black", 14)
        ).pack(pady=(25, 5))

        tk.Label(
            self,
            text=message,
            fg="white",
            bg="#1e293b",
            font=("Arial", 10),
            wraplength=340,
        ).pack(pady=5)

        self.progress_bg = tk.Frame(self, bg="#0f172a", height=4)
        self.progress_bg.pack(side="bottom", fill="x")

        self.after(4000, self.fade_out)

    def fade_out(self):
        alpha = self.attributes("-alpha")
        if alpha > 0:
            alpha -= 0.1
            self.attributes("-alpha", alpha)
            self.after(50, self.fade_out)
        else:
            self.destroy()


# =====================================================================
# 7. MAIN LABGUARD TERMINAL CLIENT
# =====================================================================
class LabGuardClient:
    def __init__(self, root):
        self.root = root
        self.is_session_active = False
        self.is_maintenance_mode = False
        self.wifi_modal = None
        self.overlay = None

        # Apply system lockdowns
        hide_taskbar()
        start_keyboard_hook()

        # Instant GUI Setup
        self.root.title("LabGuard Terminal")
        self.root.attributes("-fullscreen", True)
        self.root.attributes("-topmost", True)
        self.root.configure(bg="#0f172a")
        self.root.protocol("WM_DELETE_WINDOW", lambda: None)

        # Re-focus student ID entry if background is clicked
        self.root.bind("<Button-1>", self._on_bg_click)

        # EMERGENCY LOCK OUT SHORTCUT (Ctrl+Alt+Shift+X)
        self.root.bind("<Control-Alt-Shift-Key-X>", self.emergency_admin_exit)
        self.root.bind("<Control-Alt-Shift-Key-x>", self.emergency_admin_exit)

        # Smart focus reclamation
        def reclaim_focus(event=None):
            if not self.is_session_active:
                if self.wifi_modal and self.wifi_modal.winfo_exists():
                    self.wifi_modal.attributes("-topmost", True)
                    return
                if self.overlay and self.overlay.winfo_exists():
                    self.overlay.attributes("-topmost", True)
                    return
                self.root.attributes("-topmost", True)

        self.root.bind("<FocusOut>", reclaim_focus)
        self.root.bind("<Unmap>", reclaim_focus)

        # Top Bar for Network Status
        self.top_bar = tk.Frame(self.root, bg="#0f172a")
        self.top_bar.pack(side="top", fill="x", padx=20, pady=20)

        self.net_indicator = tk.Label(
            self.top_bar,
            text="● CONNECTING...",
            fg="#eab308",
            bg="#0f172a",
            font=("Arial", 10, "bold"),
            cursor="hand2",
        )
        self.net_indicator.pack(side="right", padx=30)
        self.net_indicator.bind("<Button-1>", lambda e: self.open_wifi_modal())

        # Main Center Container
        self.frame = tk.Frame(self.root, bg="#0f172a")
        self.frame.place(relx=0.5, rely=0.5, anchor="center")

        # Header Section
        tk.Label(
            self.frame,
            text="LABGUARD",
            fg="#D4AF37",
            bg="#0f172a",
            font=("Arial Black", 50),
        ).pack()
        tk.Label(
            self.frame,
            text=f"STATION: {PC_NUMBER}",
            fg="#64748b",
            bg="#0f172a",
            font=("Arial", 12, "bold"),
        ).pack(pady=5)

        # Standard Login Widgets Frame
        self.login_form_frame = tk.Frame(self.frame, bg="#0f172a")
        self.login_form_frame.pack(pady=10)

        tk.Label(
            self.login_form_frame,
            text="STUDENT NUMBER",
            fg="white",
            bg="#0f172a",
            font=("Arial", 9, "bold"),
        ).pack(pady=(20, 0))
        self.entry_id = tk.Entry(
            self.login_form_frame,
            font=("Arial", 18),
            justify="center",
            width=25,
            bg="#1e293b",
            fg="white",
            insertbackground="white",
            border=0,
        )
        self.entry_id.pack(pady=5, ipady=10)
        self.entry_id.bind("<Key>", self._filter_student_id_key)
        self.entry_id.bind("<KeyRelease>", self._format_student_id_entry)

        tk.Label(
            self.login_form_frame,
            text="ACCOUNT PASSWORD",
            fg="white",
            bg="#0f172a",
            font=("Arial", 9, "bold"),
        ).pack(pady=(15, 0))
        self.entry_password = tk.Entry(
            self.login_form_frame,
            font=("Arial", 18),
            justify="center",
            width=25,
            show="*",
            bg="#1e293b",
            fg="white",
            insertbackground="white",
            border=0,
        )
        self.entry_password.pack(pady=5, ipady=10)

        self.entry_id.focus_set()

        self.btn_unlock = tk.Button(
            self.login_form_frame,
            text="UNLOCK STATION",
            command=self.attempt_login,
            bg="#D4AF37",
            fg="white",
            font=("Arial", 12, "bold"),
            width=30,
            height=2,
            cursor="hand2",
            relief="flat",
        )
        self.btn_unlock.pack(pady=25)

        # Maintenance Frame (Hidden by default)
        self.maintenance_frame = tk.Frame(self.frame, bg="#0f172a")

        tk.Label(
            self.maintenance_frame,
            text="🔧",
            fg="#f59e0b",
            bg="#0f172a",
            font=("Arial", 50),
        ).pack(pady=(20, 5))
        tk.Label(
            self.maintenance_frame,
            text="STATION UNDER MAINTENANCE",
            fg="#f59e0b",
            bg="#0f172a",
            font=("Arial Black", 18),
        ).pack(pady=5)
        tk.Label(
            self.maintenance_frame,
            text="This computer is currently offline for system updates or hardware repair.\nPlease use another available terminal.",
            fg="#94a3b8",
            bg="#0f172a",
            font=("Arial", 11),
            justify="center",
            wraplength=450,
        ).pack(pady=10)

        # Bottom Trigger Options
        self.bottom_bar = tk.Frame(self.root, bg="#0f172a")
        self.bottom_bar.pack(side="bottom", pady=30)

        self.report_trigger = tk.Label(
            self.bottom_bar,
            text="⚠ HAVE A PROBLEM WITH THIS PC? CLICK HERE TO REPORT",
            fg="#ef4444",
            bg="#0f172a",
            font=("Arial", 8, "bold"),
            cursor="hand2",
        )
        self.report_trigger.pack()
        self.report_trigger.bind("<Button-1>", lambda e: self.open_report_overlay())

        self.force_on_top()

        # Deferred background hooks initialization
        self.root.after(50, self.deferred_background_init)

    def _on_bg_click(self, event):
        """Restores typing focus to the Student ID entry if student clicks the background."""
        if (
            not self.is_session_active
            and not (self.wifi_modal and self.wifi_modal.winfo_exists())
            and not (self.overlay and self.overlay.winfo_exists())
        ):
            if event.widget not in (
                self.entry_id,
                self.entry_password,
                self.btn_unlock,
            ):
                self.entry_id.focus_set()

    def deferred_background_init(self):
        """Asynchronously loads background handlers and network monitoring thread."""
        threading.Thread(target=register_shutdown_hooks, daemon=True).start()
        threading.Thread(target=self.network_monitor_loop, daemon=True).start()

    def _filter_student_id_key(self, event):
        """Block letters and keep only digits in the student ID field."""
        if event.keysym in {
            "BackSpace",
            "Delete",
            "Tab",
            "Return",
            "Left",
            "Right",
            "Up",
            "Down",
            "Home",
            "End",
        }:
            return

        if event.char and not event.char.isdigit():
            return "break"

    def _format_student_id_entry(self, event=None):
        """Smoothly auto-formats student ID without mixing up digits or misplacing cursor."""
        if event and event.keysym in {
            "BackSpace",
            "Delete",
            "Left",
            "Right",
            "Home",
            "End",
        }:
            return

        target = event.widget if event else self.entry_id
        current_value = target.get()
        cursor_pos = target.index(tk.INSERT)
        was_at_end = cursor_pos == len(current_value)

        # Extract digits only (max 12)
        digits = re.sub(r"\D", "", current_value)[:12]
        formatted = self._format_student_id_text(digits)

        if formatted != current_value:
            target.delete(0, tk.END)
            target.insert(0, formatted)

            if was_at_end:
                target.icursor(tk.END)
            else:
                old_prefix_digits = len(re.sub(r"\D", "", current_value[:cursor_pos]))
                new_pos = 0
                digit_count = 0
                for char in formatted:
                    if digit_count == old_prefix_digits:
                        break
                    if char.isdigit():
                        digit_count += 1
                    new_pos += 1
                target.icursor(new_pos)

    def _format_student_id_text(self, value):
        if len(value) <= 2:
            return value
        if len(value) <= 6:
            return f"{value[:2]}-{value[2:]}"
        return f"{value[:2]}-{value[2:6]}-{value[6:]}"

    # --- UI STATE TOGGLES ---

    def show_maintenance_ui(self):
        """Displays the maintenance message and hides login inputs."""
        if not self.is_maintenance_mode:
            self.is_maintenance_mode = True
            self.login_form_frame.pack_forget()
            self.maintenance_frame.pack(pady=10)

    def restore_login_ui(self):
        """Hides maintenance message and restores standard login inputs."""
        if self.is_maintenance_mode:
            self.is_maintenance_mode = False
            self.maintenance_frame.pack_forget()
            self.login_form_frame.pack(pady=10)
            self.entry_id.focus_set()

    # --- NETWORK & WI-FI MANAGEMENT SERVICES ---

    def network_monitor_loop(self):
        """Background thread checking server reachability & PC status continuously."""
        while True:
            try:
                lab_param = urllib.parse.quote(str(LAB_ID))
                pc_param = urllib.parse.quote(str(PC_NUMBER))
                url = f"{API_URL}/status/{lab_param}/{pc_param}"

                session = get_authenticated_session()
                res = session.get(url, timeout=3)

                if res.status_code == 200:
                    data = res.json()
                    pc_status = data.get("status") or data.get("data", {}).get("status")

                    self.root.after(0, self.update_net_status, True)

                    if pc_status and str(pc_status).lower() == "maintenance":
                        self.root.after(0, self.show_maintenance_ui)
                    else:
                        self.root.after(0, self.restore_login_ui)

                elif res.status_code == 404:
                    self.root.after(0, self.update_net_status, True)
                    self.root.after(0, self.restore_login_ui)
                else:
                    print(f"[DEBUG] Network Monitor HTTP {res.status_code}: {res.text}")
                    self.root.after(0, self.update_net_status, False)
            except Exception as e:
                print(f"[DEBUG] Network Monitor Exception: {e}")
                self.root.after(0, self.update_net_status, False)
            time.sleep(5)

    def update_net_status(self, is_online):
        """Updates the status text and color on top bar."""
        if is_online:
            self.net_indicator.config(text="● ONLINE", fg="#10b981")
        else:
            self.net_indicator.config(
                text="▲ OFFLINE (CLICK TO FIX WI-FI)", fg="#ef4444"
            )

    def open_wifi_modal(self):
        """Glassmorphic UI overlay for scanning and connecting to Wi-Fi networks."""
        if self.wifi_modal and self.wifi_modal.winfo_exists():
            self.wifi_modal.lift()
            return

        self.root.attributes("-topmost", False)
        self.wifi_modal = tk.Toplevel(self.root)
        self.wifi_modal.configure(bg="#1e293b")
        self.wifi_modal.overrideredirect(True)

        width, height = 480, 550
        screen_w = self.root.winfo_screenwidth()
        screen_h = self.root.winfo_screenheight()
        x = (screen_w // 2) - (width // 2)
        y = (screen_h // 2) - (height // 2)
        self.wifi_modal.geometry(f"{width}x{height}+{x}+{y}")
        self.wifi_modal.attributes("-topmost", True)
        self.wifi_modal.grab_set()

        def close_wifi(event=None):
            if self.wifi_modal and self.wifi_modal.winfo_exists():
                self.wifi_modal.grab_release()
                self.wifi_modal.destroy()
                self.wifi_modal = None
            self.root.attributes("-topmost", True)
            self.entry_id.focus_set()

        self.wifi_modal.bind("<Escape>", close_wifi)

        header_frame = tk.Frame(self.wifi_modal, bg="#1e293b")
        header_frame.pack(fill="x", padx=15, pady=(15, 0))

        tk.Label(
            header_frame,
            text="NETWORK SETTINGS",
            fg="#D4AF37",
            bg="#1e293b",
            font=("Arial Black", 14),
        ).pack(side="left")

        btn_x = tk.Button(
            header_frame,
            text=" ✕ ",
            command=close_wifi,
            bg="#1e293b",
            fg="#94a3b8",
            activebackground="#ef4444",
            activeforeground="white",
            font=("Arial", 12, "bold"),
            border=0,
            cursor="hand2",
        )
        btn_x.pack(side="right")

        tk.Label(
            self.wifi_modal,
            text="Select an available Wi-Fi access point to connect.",
            fg="#94a3b8",
            bg="#1e293b",
            font=("Arial", 9),
        ).pack(anchor="w", padx=15, pady=(2, 10))

        list_frame = tk.Frame(self.wifi_modal, bg="#0f172a")
        list_frame.pack(fill="both", expand=True, padx=20, pady=5)

        self.wifi_listbox = tk.Listbox(
            list_frame,
            bg="#0f172a",
            fg="white",
            font=("Arial", 11),
            selectbackground="#D4AF37",
            borderwidth=0,
            highlightthickness=0,
        )
        self.wifi_listbox.pack(side="left", fill="both", expand=True, padx=5, pady=5)

        tk.Label(
            self.wifi_modal,
            text="Security Key / Password",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=20, pady=(10, 2))

        self.wifi_pass = tk.Entry(
            self.wifi_modal,
            font=("Arial", 12),
            show="*",
            bg="#0f172a",
            fg="white",
            border=0,
            insertbackground="white",
        )
        self.wifi_pass.pack(fill="x", padx=20, pady=5, ipady=6)

        btn_frame = tk.Frame(self.wifi_modal, bg="#1e293b")
        btn_frame.pack(pady=20)

        tk.Button(
            btn_frame,
            text="SCAN WI-FI",
            command=self.scan_wifi_networks,
            bg="#3b82f6",
            fg="white",
            font=("Arial", 9, "bold"),
            width=12,
            height=2,
            relief="flat",
            cursor="hand2",
        ).pack(side="left", padx=5)

        tk.Button(
            btn_frame,
            text="CONNECT",
            command=self.connect_to_wifi,
            bg="#10b981",
            fg="white",
            font=("Arial", 9, "bold"),
            width=12,
            height=2,
            relief="flat",
            cursor="hand2",
        ).pack(side="left", padx=5)

        tk.Button(
            btn_frame,
            text="CLOSE",
            command=close_wifi,
            bg="#475569",
            fg="white",
            font=("Arial", 9, "bold"),
            width=10,
            height=2,
            relief="flat",
            cursor="hand2",
        ).pack(side="left", padx=5)

        self.wifi_pass.focus_set()
        self.scan_wifi_networks()

    def _set_wifi_list_items(self, items):
        if self.wifi_modal and self.wifi_modal.winfo_exists():
            self.wifi_listbox.delete(0, tk.END)
            for item in items:
                self.wifi_listbox.insert(tk.END, item)

    def scan_wifi_networks(self):
        """Turns Wi-Fi ON, triggers native C scan, and queries available SSIDs."""
        if self.wifi_modal and self.wifi_modal.winfo_exists():
            self.wifi_listbox.delete(0, tk.END)
            self.wifi_listbox.insert(tk.END, "Turning on Wi-Fi adapter & scanning ...")

        def execute_scan():
            enable_wifi_adapter()
            time.sleep(2.5)

            found_ssids = get_native_wifi_networks()

            if not found_ssids:
                try:
                    output = subprocess.check_output(
                        "netsh wlan show networks", shell=True, stderr=subprocess.STDOUT
                    ).decode("utf-8", errors="ignore")
                    ssids = re.findall(r"SSID\s+\d+\s*:\s*(.+)", output)
                    found_ssids = sorted(
                        list(
                            set(
                                [
                                    s.strip()
                                    for s in ssids
                                    if s.strip() and not s.strip().startswith("SSID")
                                ]
                            )
                        )
                    )
                except Exception:
                    pass

            if found_ssids:
                self.root.after(0, lambda: self._set_wifi_list_items(found_ssids))
            else:
                self.root.after(
                    0,
                    lambda: self._set_wifi_list_items(
                        ["No networks found. Try scanning again."]
                    ),
                )

        threading.Thread(target=execute_scan, daemon=True).start()

    def connect_to_wifi(self):
        """Generates a temporary XML profile and connects Windows to chosen SSID."""
        try:
            selected_ssid = self.wifi_listbox.get(self.wifi_listbox.curselection())
        except Exception:
            CinematicNotify(
                self.wifi_modal,
                "Selection Required",
                "Please click an SSID from the list.",
                color="#ef4444",
            )
            return

        password = self.wifi_pass.get().strip()

        def execute_connection():
            if password:
                security_block = f"""
                <security>
                    <authEncryption>
                        <authentication>WPA2PSK</authentication>
                        <encryption>AES</encryption>
                        <useOneX>false</useOneX>
                    </authEncryption>
                    <sharedKey>
                        <keyType>passPhrase</keyType>
                        <protected>false</protected>
                        <keyMaterial>{password}</keyMaterial>
                    </sharedKey>
                </security>"""
            else:
                security_block = """
                <security>
                    <authEncryption>
                        <authentication>open</authentication>
                        <encryption>none</encryption>
                        <useOneX>false</useOneX>
                    </authEncryption>
                </security>"""

            profile_xml = f"""<?xml version="1.0"?>
<WLANProfile xmlns="http://www.microsoft.com/networking/WLAN/profile/v1">
    <name>{selected_ssid}</name>
    <SSIDConfig><SSID><name>{selected_ssid}</name></SSID></SSIDConfig>
    <connectionType>ESS</connectionType>
    <connectionMode>auto</connectionMode>
    <MSM>{security_block}</MSM>
</WLANProfile>"""

            try:
                temp_dir = tempfile.gettempdir()
                filename = os.path.join(temp_dir, f"wifi_{hash(selected_ssid)}.xml")

                with open(filename, "w", encoding="utf-8") as f:
                    f.write(profile_xml)

                res_add = subprocess.run(
                    f'netsh wlan add profile filename="{filename}"',
                    shell=True,
                    capture_output=True,
                    text=True,
                )
                res_conn = subprocess.run(
                    f'netsh wlan connect name="{selected_ssid}"',
                    shell=True,
                    capture_output=True,
                    text=True,
                )

                if os.path.exists(filename):
                    os.remove(filename)

                if res_conn.returncode == 0:
                    CinematicNotify(
                        self.wifi_modal,
                        "Connecting",
                        f"Connecting to {selected_ssid}...",
                        color="#3b82f6",
                    )
                else:
                    CinematicNotify(
                        self.wifi_modal,
                        "Wi-Fi Error",
                        "Could not connect to target network.",
                        color="#ef4444",
                    )

            except Exception as e:
                CinematicNotify(
                    self.wifi_modal,
                    "Wi-Fi Error",
                    f"Profile creation failed: {e}",
                    color="#ef4444",
                )

        threading.Thread(target=execute_connection, daemon=True).start()

    # --- REPORTING OVERLAY & LOGIN HANDLERS ---

    def open_report_overlay(self):
        if self.overlay and self.overlay.winfo_exists():
            self.overlay.lift()
            return

        self.root.attributes("-topmost", False)
        self.overlay = tk.Toplevel(self.root)
        self.overlay.configure(bg="#1e293b")
        self.overlay.overrideredirect(True)

        width, height = 500, 640
        screen_w = self.root.winfo_screenwidth()
        screen_h = self.root.winfo_screenheight()
        x = (screen_w // 2) - (width // 2)
        y = (screen_h // 2) - (height // 2)
        self.overlay.geometry(f"{width}x{height}+{x}+{y}")

        self.overlay.attributes("-topmost", True)
        self.overlay.grab_set()

        tk.Label(
            self.overlay,
            text="REPORT AN ISSUE",
            fg="#D4AF37",
            bg="#1e293b",
            font=("Arial Black", 16),
        ).pack(pady=(25, 5))

        tk.Label(
            self.overlay,
            text="Confirm Student Number",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=60, pady=(15, 2))
        self.report_student_id = tk.Entry(
            self.overlay,
            font=("Arial", 12),
            bg="#0f172a",
            fg="white",
            border=0,
            insertbackground="white",
        )
        self.report_student_id.pack(fill="x", padx=60, ipady=6)
        self.report_student_id.bind("<Key>", self._filter_student_id_key)
        self.report_student_id.bind("<KeyRelease>", self._format_student_id_entry)

        tk.Label(
            self.overlay,
            text="Confirm Password",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=60, pady=(10, 2))
        self.report_password = tk.Entry(
            self.overlay,
            font=("Arial", 12),
            show="*",
            bg="#0f172a",
            fg="white",
            border=0,
            insertbackground="white",
        )
        self.report_password.pack(fill="x", padx=60, ipady=6)

        tk.Label(
            self.overlay,
            text="Select Category",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=60, pady=(15, 2))
        self.issue_var = tk.StringVar(value="Hardware Issue")

        dropdown = tk.OptionMenu(
            self.overlay,
            self.issue_var,
            "Hardware Issue",
            "Software/App Error",
            "No Internet",
            "Peripheral (Mouse/KB)",
        )
        dropdown.config(
            bg="#0f172a",
            fg="white",
            activebackground="#D4AF37",
            font=("Arial", 10),
            relief="flat",
            borderwidth=0,
        )
        dropdown.pack(fill="x", padx=60)

        tk.Label(
            self.overlay,
            text="Describe the Problem",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=60, pady=(15, 2))
        self.remarks_box = tk.Text(
            self.overlay,
            height=4,
            font=("Arial", 11),
            bg="#0f172a",
            fg="white",
            border=0,
            padx=15,
            pady=10,
            insertbackground="white",
        )
        self.remarks_box.pack(padx=60, fill="x")

        self.report_student_id.focus_set()

        def close_overlay():
            if self.overlay and self.overlay.winfo_exists():
                self.overlay.grab_release()
                self.overlay.destroy()
                self.overlay = None
            self.root.attributes("-topmost", True)
            self.entry_id.focus_set()

        def handle_submit():
            student_id = self.report_student_id.get().strip()
            password = self.report_password.get()
            remarks = self.remarks_box.get("1.0", tk.END).strip()

            if not student_id or not password:
                CinematicNotify(
                    self.overlay,
                    "Identity Required",
                    "Credentials required to verify report authenticity.",
                    color="#ef4444",
                )
                return
            if not remarks:
                CinematicNotify(
                    self.overlay,
                    "Incomplete",
                    "Please detail the issue descriptions.",
                    color="#ef4444",
                )
                return

            self.btn_send.config(state="disabled", text="VERIFYING & SENDING...")

            payload = {
                "pc_number": PC_NUMBER,
                "student_id": student_id,
                "password": password,
                "issue_type": self.issue_var.get(),
                "remarks": remarks,
            }

            def async_report():
                try:
                    session = get_authenticated_session()
                    response = session.post(
                        f"{API_URL}/alerts",
                        json=payload,
                        timeout=8,
                    )

                    if response.status_code in [200, 201]:
                        self.root.after(
                            0,
                            lambda: CinematicNotify(
                                self.root,
                                "Report Logged",
                                "Identity verified and ticket created.",
                                color="#10b981",
                            ),
                        )
                        self.root.after(0, close_overlay)
                    else:
                        msg = response.json().get(
                            "message", "Identity verification failed."
                        )
                        self.root.after(
                            0,
                            lambda: CinematicNotify(
                                self.overlay, "Auth Failure", msg, color="#ef4444"
                            ),
                        )
                        self.root.after(
                            0,
                            lambda: self.btn_send.config(
                                state="normal", text="SEND REPORT"
                            ),
                        )
                except Exception:
                    self.root.after(
                        0,
                        lambda: CinematicNotify(
                            self.overlay,
                            "Connection Error",
                            "Could not connect to database server.",
                            color="#ef4444",
                        ),
                    )
                    self.root.after(
                        0,
                        lambda: self.btn_send.config(
                            state="normal", text="SEND REPORT"
                        ),
                    )

            threading.Thread(target=async_report, daemon=True).start()

        btn_container = tk.Frame(self.overlay, bg="#1e293b")
        btn_container.pack(pady=25)

        self.btn_send = tk.Button(
            btn_container,
            text="SEND REPORT",
            command=handle_submit,
            bg="#ef4444",
            fg="white",
            font=("Arial", 9, "bold"),
            width=18,
            height=2,
            relief="flat",
        )
        self.btn_send.pack(side="left", padx=10)

        tk.Button(
            btn_container,
            text="CANCEL",
            command=close_overlay,
            bg="#475569",
            fg="white",
            font=("Arial", 9, "bold"),
            width=15,
            height=2,
            relief="flat",
        ).pack(side="left", padx=10)

    def force_on_top(self):
        """Forces the lock screen or active popup to stay topmost without stealing entry focus."""
        if not self.is_session_active:
            if self.wifi_modal and self.wifi_modal.winfo_exists():
                self.wifi_modal.lift()
                self.wifi_modal.attributes("-topmost", True)
            elif self.overlay and self.overlay.winfo_exists():
                self.overlay.lift()
                self.overlay.attributes("-topmost", True)
            else:
                self.root.lift()
                self.root.attributes("-topmost", True)
        self.root.after(1000, self.force_on_top)

    def attempt_login(self):
        student_id = self.entry_id.get().strip()
        password = self.entry_password.get()

        if not student_id or not password:
            CinematicNotify(
                self.root, "Input Required", "Enter your credentials.", color="#D4AF37"
            )
            return

        self.btn_unlock.config(state="disabled", text="VERIFYING...")
        payload = {
            "pc_number": PC_NUMBER,
            "student_id": student_id,
            "password": password,
        }

        def perform_login():
            try:
                session = get_authenticated_session()
                response = session.post(
                    f"{API_URL}/login",
                    json=payload,
                    timeout=10,
                )
                if response.status_code == 200:
                    name = response.json().get("name", "User")
                    self.root.after(
                        0,
                        lambda: CinematicNotify(
                            self.root,
                            "Authorized",
                            f"Welcome, {name}!",
                            color="#10b981",
                        ),
                    )
                    self.root.after(1500, self.hide_terminal)
                else:
                    try:
                        msg = response.json().get("message", "Invalid Credentials.")
                    except Exception:
                        msg = f"HTTP Error {response.status_code}"
                    print(
                        f"[DEBUG] Login Failed ({response.status_code}): {response.text}"
                    )
                    self.root.after(
                        0,
                        lambda: CinematicNotify(
                            self.root, "Auth Failed", msg, color="#ef4444"
                        ),
                    )
                    self.root.after(
                        0,
                        lambda: self.btn_unlock.config(
                            state="normal", text="UNLOCK STATION"
                        ),
                    )
            except Exception as e:
                print(f"[DEBUG] Login Exception: {e}")
                self.root.after(
                    0,
                    lambda: CinematicNotify(
                        self.root,
                        "Error",
                        "Server is offline. Check Wi-Fi connection.",
                        color="#ef4444",
                    ),
                )
                self.root.after(
                    0,
                    lambda: self.btn_unlock.config(
                        state="normal", text="UNLOCK STATION"
                    ),
                )

        threading.Thread(target=perform_login, daemon=True).start()

    def hide_terminal(self):
        """Unlocks the PC session: Hides screen, restores Taskbar, and unhooks keyboard."""
        self.is_session_active = True
        self.root.attributes("-topmost", False)

        # Restore environment for active session
        show_taskbar()
        stop_keyboard_hook()

        self.root.withdraw()
        threading.Thread(target=self.heartbeat_loop, daemon=True).start()

    def heartbeat_loop(self):
        """Active session heartbeat loop monitoring network connection & server status."""
        time.sleep(3)
        consecutive_failures = 0

        while True:
            try:
                lab_param = urllib.parse.quote(str(LAB_ID))
                pc_param = urllib.parse.quote(str(PC_NUMBER))
                url = f"{API_URL}/status/{lab_param}/{pc_param}"

                session = get_authenticated_session()
                response = session.get(url, timeout=5)

                if response.status_code == 200:
                    consecutive_failures = 0  # Reset failure counter on successful ping
                    data = response.json()
                    pc_status = data.get("status") or data.get("data", {}).get("status")

                    if pc_status and str(pc_status).lower() in [
                        "available",
                        "unoccupied",
                        "released",
                        "offline",
                        "maintenance",
                    ]:
                        self.root.after(0, self.lock_ui_again)
                        break
            except Exception as e:
                consecutive_failures += 1
                print(f"[DEBUG] Heartbeat Ping Failed ({consecutive_failures}/3): {e}")

                # If Wi-Fi is turned off or disconnected for 15 seconds (3 pings), lock terminal
                if consecutive_failures >= 3:
                    print(
                        "[DEBUG] Wi-Fi connection lost for 15s. Auto-locking station..."
                    )
                    self.root.after(0, self.lock_ui_again)
                    break

            time.sleep(5)

    def lock_ui_again(self):
        """Restores the UI and resets entry inputs on thread-safe main thread."""
        self.is_session_active = False
        self.entry_id.delete(0, tk.END)
        self.entry_password.delete(0, tk.END)
        self.btn_unlock.config(state="normal", text="UNLOCK STATION")

        # Apply system lockdowns
        hide_taskbar()
        start_keyboard_hook()

        self.root.deiconify()
        self.root.lift()
        self.root.attributes("-topmost", True)

    def emergency_admin_exit(self, event=None):
        """Emergency exit shortcut for administrators."""
        print("[ADMIN] Emergency exit triggered. Cleaning up system hooks...")
        cleanup_security()
        self.root.destroy()
        sys.exit(0)


# =====================================================================
# 8. APPLICATION LAUNCHER
# =====================================================================
if __name__ == "__main__":
    app_root = tk.Tk()
    client = LabGuardClient(app_root)
    app_root.update()
    app_root.mainloop()