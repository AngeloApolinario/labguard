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

# System Tray support (pystray + Pillow)
try:
    import pystray
    from PIL import Image, ImageDraw
    TRAY_AVAILABLE = True
except ImportError:
    TRAY_AVAILABLE = False

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
VK_F4 = 0x73    # F4 key

ULONG_PTR = ctypes.c_ulonglong if ctypes.sizeof(ctypes.c_void_p) == 8 else ctypes.c_ulong
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
_hook_proc_ref = None

user32 = ctypes.windll.user32
kernel32 = ctypes.windll.kernel32

HOOKPROC = ctypes.WINFUNCTYPE(LRESULT, ctypes.c_int, wintypes.WPARAM, wintypes.LPARAM)

user32.CallNextHookEx.argtypes = [wintypes.HANDLE, ctypes.c_int, wintypes.WPARAM, wintypes.LPARAM]
user32.CallNextHookEx.restype = LRESULT

user32.SetWindowsHookExW.argtypes = [ctypes.c_int, HOOKPROC, wintypes.HINSTANCE, wintypes.DWORD]
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
            user32.ShowWindow(hwnd_primary, 0)
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
            user32.ShowWindow(hwnd_primary, 5)

        hwnd_secondary = user32.FindWindowW("Shell_SecondaryTrayWnd", None)
        if hwnd_secondary:
            user32.EnableWindow(hwnd_secondary, True)
            user32.ShowWindow(hwnd_secondary, 5)
        print("[DEBUG] Taskbar restored.")
    except Exception as e:
        print(f"[DEBUG] Error showing taskbar: {e}")


# =====================================================================
# 3. HIGH-SPEED LOW-LEVEL KEYBOARD HOOK
# =====================================================================
def _low_level_keyboard_proc(nCode, wParam, lParam):
    try:
        if nCode >= 0 and lParam:
            kb_struct = KBDLLHOOKSTRUCT.from_address(lParam)
            vk_code = kb_struct.vkCode

            if vk_code not in (VK_LWIN, VK_RWIN, VK_TAB, VK_ESCAPE, VK_F4):
                return user32.CallNextHookEx(_hook_id, nCode, wParam, lParam)

            flags = kb_struct.flags
            is_alt_pressed = bool(flags & 0x20) or (user32.GetAsyncKeyState(VK_MENU) & 0x8000) != 0

            # Block Windows Keys
            if vk_code in (VK_LWIN, VK_RWIN):
                return 1

            # Block Alt + Tab
            if vk_code == VK_TAB and is_alt_pressed:
                return 1

            # Block Alt + Esc
            if vk_code == VK_ESCAPE and is_alt_pressed:
                return 1

            # Block Ctrl + Esc & Task Manager shortcuts
            if vk_code == VK_ESCAPE:
                is_ctrl_pressed = (user32.GetAsyncKeyState(VK_CONTROL) & 0x8000) != 0
                if is_ctrl_pressed:
                    return 1

            # Block Alt + F4
            if vk_code == VK_F4 and is_alt_pressed:
                return 1

    except Exception as e:
        print(f"[DEBUG] Hook Procedure Error: {e}")

    return user32.CallNextHookEx(_hook_id, nCode, wParam, lParam)


def start_keyboard_hook():
    global _hook_id, _hook_proc_ref
    if _hook_id is not None:
        return

    try:
        _hook_proc_ref = HOOKPROC(_low_level_keyboard_proc)
        _hook_id = user32.SetWindowsHookExW(WH_KEYBOARD_LL, _hook_proc_ref, None, 0)
        if not _hook_id or _hook_id == 0:
            err = kernel32.GetLastError()
            print(f"[DEBUG] SetWindowsHookExW FAILED: {err}")
            _hook_id = None
        else:
            print(f"[DEBUG] Keyboard Hook installed! Hook ID: {_hook_id}")
    except Exception as e:
        print(f"[DEBUG] Keyboard Hook exception: {e}")


def stop_keyboard_hook():
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
    if getattr(sys, "frozen", False):
        base_dir = os.path.dirname(sys.executable)
    else:
        base_dir = os.path.dirname(os.path.abspath(__file__))

    config_path = os.path.join(base_dir, "config.json")
    config_data = {
        "server_url": "https://labguard.test/api/pc",
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


config = load_config()
API_URL = config.get("server_url", "https://labguard.it.com/api/pc").rstrip('/')
if not API_URL.endswith('/api/pc'):
    API_URL += '/api/pc'

LAB_ID = str(config.get("lab", "LAB 1")).strip()
PC_NUMBER = str(config.get("pc", "PC-01")).strip()
HEADERS = {"Accept": "application/json"}


def get_authenticated_session():
    session = requests.Session()
    session.verify = False
    base_domain = API_URL.split("/api")[0]
    try:
        session.get(f"{base_domain}/sanctum/csrf-cookie", headers=HEADERS, timeout=5)
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
    stop_keyboard_hook()
    show_taskbar()


def send_logout_signal():
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
    print("Force shutdown detected...")
    send_logout_signal()
    sys.exit(0)


signal.signal(signal.SIGINT, handle_exit_signal)
signal.signal(signal.SIGTERM, handle_exit_signal)
atexit.register(cleanup_security)


def register_shutdown_hooks():
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
# 5. WI-FI MANAGEMENT SERVICES
# =====================================================================
def turn_on_wifi_radio_native():
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

        if wlanapi.WlanOpenHandle(2, None, ctypes.byref(pVersion), ctypes.byref(hClient)) == 0:
            pList = ctypes.POINTER(WLAN_INTERFACE_INFO_LIST)()
            if wlanapi.WlanEnumInterfaces(hClient, None, ctypes.byref(pList)) == 0:
                if pList.contents.dwNumberOfItems > 0:
                    guid = pList.contents.InterfaceInfo[0].InterfaceGuid
                    radio_state = WLAN_PHY_RADIO_STATE(0, 1, 1)
                    wlanapi.WlanSetInterface(
                        hClient, ctypes.byref(guid), 4, ctypes.sizeof(WLAN_PHY_RADIO_STATE),
                        ctypes.byref(radio_state), None
                    )
                    wlanapi.WlanScan(hClient, ctypes.byref(guid), None, None, None)
                wlanapi.WlanFreeMemory(pList)
            wlanapi.WlanCloseHandle(hClient, None)
            return True
    except Exception as e:
        print(f"Native radio activation failed: {e}")
    return False


def get_native_wifi_networks():
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

        if wlanapi.WlanOpenHandle(2, None, ctypes.byref(pVersion), ctypes.byref(hClient)) == 0:
            pList = ctypes.POINTER(WLAN_INTERFACE_INFO_LIST)()
            if wlanapi.WlanEnumInterfaces(hClient, None, ctypes.byref(pList)) == 0:
                if pList.contents.dwNumberOfItems > 0:
                    guid = pList.contents.InterfaceInfo[0].InterfaceGuid
                    pNetList = ctypes.POINTER(WLAN_AVAILABLE_NETWORK_LIST)()
                    if wlanapi.WlanGetAvailableNetworkList(hClient, ctypes.byref(guid), 2, None, ctypes.byref(pNetList)) == 0:
                        num_items = pNetList.contents.dwNumberOfItems
                        base_ptr = ctypes.addressof(pNetList.contents.Network)
                        stride = ctypes.sizeof(WLAN_AVAILABLE_NETWORK)
                        for i in range(num_items):
                            net = WLAN_AVAILABLE_NETWORK.from_address(base_ptr + i * stride)
                            ssid_len = net.dot11Ssid.uSSIDLength
                            if 0 < ssid_len <= 32:
                                ssid_bytes = bytes(net.dot11Ssid.ucSSID[:ssid_len])
                                ssid_str = ssid_bytes.decode("utf-8", errors="ignore").strip()
                                if ssid_str:
                                    ssids.append(ssid_str)
                        wlanapi.WlanFreeMemory(pNetList)
                wlanapi.WlanFreeMemory(pList)
            wlanapi.WlanCloseHandle(hClient, None)
    except Exception as e:
        print(f"Native network list failed: {e}")

    return sorted(list(set(ssids)))


def enable_wifi_adapter():
    try:
        subprocess.run(
            [
                "powershell", "-NoProfile", "-Command",
                "Get-NetAdapter | Where-Object { $_.Name -match 'Wi-Fi|Wireless|WiFi' } | Enable-NetAdapter -Confirm:$false",
            ],
            capture_output=True, text=True, check=False,
        )
    except Exception:
        pass
    turn_on_wifi_radio_native()
    return True, "Wi-Fi enabled."


# =====================================================================
# 6. GLASSMORPHIC NOTIFICATION OVERLAY (UPGRADED READ TIME + DISMISS)
# =====================================================================
class CinematicNotify(tk.Toplevel):
    def __init__(self, parent, title, message, color="#D4AF37"):
        super().__init__(parent)
        self.overrideredirect(True)
        self.attributes("-topmost", True)
        self.attributes("-alpha", 1.0)
        self.configure(bg="#1e293b", highlightbackground=color, highlightthickness=2)

        p_w = parent.winfo_screenwidth()
        p_h = parent.winfo_screenheight()
        width, height = 420, 170
        x = (p_w // 2) - (width // 2)
        y = (p_h // 2) - (height // 2)
        self.geometry(f"{width}x{height}+{x}+{y}")

        # Click notification anywhere to dismiss instantly
        self.bind("<Button-1>", lambda e: self.destroy())

        tk.Label(self, text=title.upper(), fg=color, bg="#1e293b", font=("Arial Black", 14), cursor="hand2").pack(pady=(22, 4))
        tk.Label(self, text=message, fg="white", bg="#1e293b", font=("Arial", 10), wraplength=360, cursor="hand2").pack(pady=4)

        tk.Label(self, text="Click to dismiss", fg="#64748b", bg="#1e293b", font=("Arial", 8)).pack(pady=(2, 0))

        self.progress_bg = tk.Frame(self, bg="#0f172a", height=4)
        self.progress_bg.pack(side="bottom", fill="x")

        # Display for 6.5 seconds (gives ample time to read)
        self.after(6500, self.fade_out)

    def fade_out(self):
        try:
            alpha = float(self.attributes("-alpha"))
            if alpha > 0.05:
                alpha -= 0.05
                self.attributes("-alpha", alpha)
                self.after(40, self.fade_out)
            else:
                self.destroy()
        except Exception:
            pass


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
        self.tray_icon = None
        self.floating_pill = None

        # Authenticated student/staff credentials & session data
        self.current_student = {
            "id": "",
            "password": "",
            "name": "",
            "role": "student",
            "session_id": None,
        }

        # Apply initial system lockdowns
        hide_taskbar()
        start_keyboard_hook()

        # Instant GUI Setup
        self.root.title("LabGuard Terminal")
        self.root.attributes("-fullscreen", True)
        self.root.attributes("-topmost", True)
        self.root.configure(bg="#0f172a")
        self.root.protocol("WM_DELETE_WINDOW", lambda: None)

        self.root.bind("<Button-1>", self._on_bg_click)
        self.root.bind("<Control-Alt-Shift-Key-X>", self.emergency_admin_exit)
        self.root.bind("<Control-Alt-Shift-Key-x>", self.emergency_admin_exit)

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

        # Top Bar
        self.top_bar = tk.Frame(self.root, bg="#0f172a")
        self.top_bar.pack(side="top", fill="x", padx=25, pady=20)

        self.station_badge = tk.Label(
            self.top_bar,
            text=f"LAB: {LAB_ID}  •  {PC_NUMBER}",
            fg="#94a3b8",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
            padx=12,
            pady=6,
        )
        self.station_badge.pack(side="left")

        self.net_indicator = tk.Label(
            self.top_bar,
            text="● CONNECTING...",
            fg="#eab308",
            bg="#0f172a",
            font=("Arial", 10, "bold"),
            cursor="hand2",
        )
        self.net_indicator.pack(side="right")
        self.net_indicator.bind("<Button-1>", lambda e: self.open_wifi_modal())

        # Main Center Container
        self.main_container = tk.Frame(self.root, bg="#0f172a")
        self.main_container.place(relx=0.5, rely=0.5, anchor="center")

        # -------------------------------------------------------------
        # PAGE 1: LOGIN UI
        # -------------------------------------------------------------
        self.login_view = tk.Frame(self.main_container, bg="#0f172a")
        self.login_view.pack()

        tk.Label(
            self.login_view,
            text="LABGUARD",
            fg="#D4AF37",
            bg="#0f172a",
            font=("Arial Black", 48),
        ).pack()

        tk.Label(
            self.login_view,
            text="TERMINAL ACCESS MANAGEMENT SYSTEM",
            fg="#64748b",
            bg="#0f172a",
            font=("Arial", 10, "bold"),
        ).pack(pady=(0, 20))

        self.login_form_frame = tk.Frame(self.login_view, bg="#0f172a")
        self.login_form_frame.pack(pady=5)

        tk.Label(
            self.login_form_frame,
            text="ID NUMBER",
            fg="#cbd5e1",
            bg="#0f172a",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=10, pady=(10, 3))

        self.entry_id = tk.Entry(
            self.login_form_frame,
            font=("Arial", 16),
            justify="center",
            width=26,
            bg="#1e293b",
            fg="white",
            insertbackground="white",
            border=0,
        )
        self.entry_id.pack(ipady=10)
        self.entry_id.bind("<Key>", self._filter_student_id_key)
        self.entry_id.bind("<KeyRelease>", self._format_student_id_entry)
        self.entry_id.bind("<Return>", lambda e: self.entry_password.focus_set())

        tk.Label(
            self.login_form_frame,
            text="ACCOUNT PASSWORD",
            fg="#cbd5e1",
            bg="#0f172a",
            font=("Arial", 9, "bold"),
        ).pack(anchor="w", padx=10, pady=(15, 3))

        self.entry_password = tk.Entry(
            self.login_form_frame,
            font=("Arial", 16),
            justify="center",
            width=26,
            show="*",
            bg="#1e293b",
            fg="white",
            insertbackground="white",
            border=0,
        )
        self.entry_password.pack(ipady=10)
        self.entry_password.bind("<Return>", lambda e: self.attempt_login())

        self.entry_id.focus_set()

        self.btn_unlock = tk.Button(
            self.login_form_frame,
            text="AUTHENTICATE & INSPECT",
            command=self.attempt_login,
            bg="#D4AF37",
            fg="#0f172a",
            activebackground="#b89628",
            activeforeground="#0f172a",
            font=("Arial Black", 11),
            width=26,
            height=2,
            cursor="hand2",
            relief="flat",
        )
        self.btn_unlock.pack(pady=25)

        # Maintenance UI
        self.maintenance_frame = tk.Frame(self.login_view, bg="#0f172a")
        tk.Label(self.maintenance_frame, text="🔧", fg="#f59e0b", bg="#0f172a", font=("Arial", 46)).pack(pady=(15, 5))
        tk.Label(self.maintenance_frame, text="TERMINAL UNDER MAINTENANCE", fg="#f59e0b", bg="#0f172a", font=("Arial Black", 16)).pack(pady=5)
        tk.Label(
            self.maintenance_frame,
            text="This workstation is currently offline for routine servicing or repairs.\nPlease transfer to another available station.",
            fg="#94a3b8",
            bg="#0f172a",
            font=("Arial", 11),
            justify="center",
            wraplength=440,
        ).pack(pady=10)

        # -------------------------------------------------------------
        # PAGE 2: HARDWARE INTEGRITY CHECKLIST UI
        # -------------------------------------------------------------
        self.checklist_view = tk.Frame(self.main_container, bg="#0f172a")

        self.force_on_top()
        self.root.after(50, self.deferred_background_init)

    def _on_bg_click(self, event):
        if (
            not self.is_session_active
            and not (self.wifi_modal and self.wifi_modal.winfo_exists())
            and not (self.overlay and self.overlay.winfo_exists())
        ):
            if event.widget not in (self.entry_id, self.entry_password, self.btn_unlock):
                if self.login_view.winfo_ismapped():
                    self.entry_id.focus_set()

    def deferred_background_init(self):
        threading.Thread(target=register_shutdown_hooks, daemon=True).start()
        threading.Thread(target=self.network_monitor_loop, daemon=True).start()

    def _filter_student_id_key(self, event):
        if event.keysym in {
            "BackSpace", "Delete", "Tab", "Return", "Left", "Right", "Up", "Down", "Home", "End"
        }:
            return
        if event.char and not event.char.isdigit():
            return "break"

    def _format_student_id_entry(self, event=None):
        if event and event.keysym in {"BackSpace", "Delete", "Left", "Right", "Home", "End"}:
            return

        target = event.widget if event else self.entry_id
        current_value = target.get()
        cursor_pos = target.index(tk.INSERT)
        was_at_end = cursor_pos == len(current_value)

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

    def show_maintenance_ui(self):
        if not self.is_maintenance_mode:
            self.is_maintenance_mode = True
            self.login_form_frame.pack_forget()
            self.maintenance_frame.pack(pady=10)

    def restore_login_ui(self):
        if self.is_maintenance_mode:
            self.is_maintenance_mode = False
            self.maintenance_frame.pack_forget()
            self.login_form_frame.pack(pady=10)
            self.entry_id.focus_set()

    # =================================================================
    # PAGE 2: BUILD HARDWARE CHECKLIST (6 SPECIFIED HARDWARE ITEMS)
    # =================================================================
    def show_checklist_screen(self, student_name):
        self.login_view.pack_forget()
        for widget in self.checklist_view.winfo_children():
            widget.destroy()

        self.checklist_view.pack(fill="both", expand=True)

        badge_frame = tk.Frame(self.checklist_view, bg="#0f172a")
        badge_frame.pack(pady=(0, 6))

        tk.Label(
            badge_frame,
            text="STEP 2 OF 2 • MANDATORY WORKSTATION AUDIT",
            fg="#D4AF37",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
            padx=14,
            pady=4,
        ).pack()

        tk.Label(
            self.checklist_view,
            text=f"Welcome, {student_name.upper()}!",
            fg="white",
            bg="#0f172a",
            font=("Arial Black", 22),
        ).pack(pady=(6, 2))

        tk.Label(
            self.checklist_view,
            text="Inspect all workstation peripherals. Check items that are operational, leave defective items unchecked.\nUnchecked components will be recorded as non-operational in your session report.",
            fg="#94a3b8",
            bg="#0f172a",
            font=("Arial", 10),
            justify="center",
            wraplength=650,
        ).pack(pady=(0, 16))

        # UPDATED 6 CHECKLIST ITEMS AS SPECIFIED:
        self.hardware_items = [
            {"id": "system_unit", "icon": "🖥️", "title": "System Unit", "desc": "Power button working, casing sealed, no abnormal fan noise."},
            {"id": "monitor",     "icon": "🖥️", "title": "Display Monitor", "desc": "Screen clear, no cracks, lines, or video signal loss."},
            {"id": "avr",         "icon": "⚡", "title": "Power Unit (AVR)", "desc": "Voltage regulator active, power indicator light on, grounded."},
            {"id": "mouse",       "icon": "🖱️", "title": "Optical Mouse", "desc": "Laser tracking smooth, left & right click responsive."},
            {"id": "keyboard",    "icon": "⌨️", "title": "Keyboard Unit", "desc": "All keycaps present, typing responsive, no sticky keys."},
            {"id": "cables",      "icon": "🔌", "title": "Power & I/O Cables", "desc": "Display, power, and peripheral cords securely plugged in."},
        ]

        self.check_states = {}
        cards_container = tk.Frame(self.checklist_view, bg="#0f172a")
        cards_container.pack(pady=4)

        for idx, item in enumerate(self.hardware_items):
            var = tk.BooleanVar(value=False)
            self.check_states[item["id"]] = var

            r = idx // 2
            c = idx % 2

            card = tk.Frame(cards_container, bg="#1e293b", width=310, height=65, highlightthickness=1, highlightbackground="#334155")
            card.grid(row=r, column=c, padx=8, pady=6, sticky="nsew")
            card.pack_propagate(False)

            lbl_check = tk.Label(card, text="[   ]", fg="#64748b", bg="#1e293b", font=("Courier", 12, "bold"), cursor="hand2")
            lbl_check.pack(side="left", padx=(12, 6))

            content = tk.Frame(card, bg="#1e293b", cursor="hand2")
            content.pack(side="left", fill="both", expand=True, pady=6)

            title_lbl = tk.Label(
                content,
                text=f"{item['icon']} {item['title']}",
                fg="#f8fafc",
                bg="#1e293b",
                font=("Arial", 10, "bold"),
                anchor="w",
            )
            title_lbl.pack(anchor="w")

            desc_lbl = tk.Label(
                content,
                text=item["desc"],
                fg="#94a3b8",
                bg="#1e293b",
                font=("Arial", 8),
                anchor="w",
                wraplength=230,
                justify="left",
            )
            desc_lbl.pack(anchor="w")

            def toggle_item(v=var, lbl=lbl_check, crd=card):
                new_val = not v.get()
                v.set(new_val)
                if new_val:
                    lbl.config(text="[ ✔ ]", fg="#10b981")
                    crd.config(highlightbackground="#10b981")
                else:
                    lbl.config(text="[ ✕ ]", fg="#ef4444")
                    crd.config(highlightbackground="#ef4444")
                self._update_checklist_button_state()

            card.bind("<Button-1>", lambda e, func=toggle_item: func())
            lbl_check.bind("<Button-1>", lambda e, func=toggle_item: func())
            content.bind("<Button-1>", lambda e, func=toggle_item: func())
            title_lbl.bind("<Button-1>", lambda e, func=toggle_item: func())
            desc_lbl.bind("<Button-1>", lambda e, func=toggle_item: func())

        quick_select_frame = tk.Frame(self.checklist_view, bg="#0f172a")
        quick_select_frame.pack(pady=(8, 12))

        btn_select_all = tk.Button(
            quick_select_frame,
            text="✔ SELECT ALL AS OPERATIONAL",
            command=self.select_all_checklist_items,
            bg="#334155",
            fg="#cbd5e1",
            activebackground="#475569",
            activeforeground="white",
            font=("Arial", 8, "bold"),
            relief="flat",
            padx=12,
            pady=4,
            cursor="hand2",
        )
        btn_select_all.pack()

        action_frame = tk.Frame(self.checklist_view, bg="#0f172a")
        action_frame.pack(pady=5)

        # Unrestricted: Can proceed even if some items are left unchecked
        self.btn_proceed = tk.Button(
            action_frame,
            text="CONFIRM & PROCEED TO DESKTOP",
            command=self.complete_checklist_and_unlock,
            bg="#10b981",
            fg="white",
            activebackground="#059669",
            activeforeground="white",
            font=("Arial Black", 10),
            width=32,
            height=2,
            relief="flat",
            state="normal",
            cursor="hand2",
        )
        self.btn_proceed.pack(side="left", padx=8)

        btn_cancel = tk.Button(
            action_frame,
            text="CANCEL & LOGOUT",
            command=self.return_to_login_screen,
            bg="#1e293b",
            fg="#94a3b8",
            font=("Arial", 9, "bold"),
            width=18,
            height=2,
            relief="flat",
            cursor="hand2",
        )
        btn_cancel.pack(side="left", padx=8)

        # Relocated report trigger
        report_pill = tk.Frame(self.checklist_view, bg="#1e293b", cursor="hand2")
        report_pill.pack(pady=(18, 0))

        lbl_warn_icon = tk.Label(report_pill, text="⚠", fg="#ef4444", bg="#1e293b", font=("Arial", 11, "bold"), cursor="hand2")
        lbl_warn_icon.pack(side="left", padx=(14, 4), pady=6)

        lbl_warn_text = tk.Label(
            report_pill,
            text="SOMETHING BROKEN OR MISSING? CLICK HERE TO LOG A FORMAL TICKET",
            fg="#ef4444",
            bg="#1e293b",
            font=("Arial", 8, "bold"),
            cursor="hand2",
        )
        lbl_warn_text.pack(side="left", padx=(0, 14), pady=6)

        for w in (report_pill, lbl_warn_icon, lbl_warn_text):
            w.bind("<Button-1>", lambda e: self.open_report_overlay(prefill=True))

    def select_all_checklist_items(self):
        self.show_checklist_screen(self.current_student["name"])
        for item_id, var in self.check_states.items():
            var.set(True)
        self._update_checklist_button_state()

    def _update_checklist_button_state(self):
        checked_count = sum(1 for v in self.check_states.values() if v.get())
        if checked_count == len(self.check_states):
            self.btn_proceed.config(
                text="CONFIRM & PROCEED TO DESKTOP",
                bg="#10b981",
                fg="white",
            )
        else:
            self.btn_proceed.config(
                text=f"PROCEED ({checked_count}/{len(self.check_states)} OPERATIONAL)",
                bg="#f59e0b",
                fg="#0f172a",
            )

    def return_to_login_screen(self):
        self.current_student = {"id": "", "password": "", "name": "", "role": "student", "session_id": None}
        self.checklist_view.pack_forget()
        self.login_view.pack()
        self.entry_password.delete(0, tk.END)
        self.btn_unlock.config(state="normal", text="AUTHENTICATE & INSPECT")
        self.entry_id.focus_set()

    # =================================================================
    # SUBMIT CHECKLIST TO LARAVEL BACKEND & UNLOCK
    # =================================================================
    def complete_checklist_and_unlock(self):
        self.btn_proceed.config(state="disabled", text="SAVING INSPECTION AUDIT...")

        checklist_payload = {
            item_id: var.get() for item_id, var in self.check_states.items()
        }

        request_body = {
            "pc_number": PC_NUMBER,
            "lab": LAB_ID,
            "student_id": self.current_student["id"],
            "session_id": self.current_student["session_id"],
            "checklist": checklist_payload,
        }

        def send_checklist_to_laravel():
            try:
                session = get_authenticated_session()
                resp = session.post(f"{API_URL}/checklist", json=request_body, timeout=6)
                if resp.status_code in (200, 201):
                    print(f"[AUDIT] Workstation checklist saved to Laravel successfully.")
                else:
                    print(f"[AUDIT WARN] Backend checklist response: {resp.status_code} - {resp.text}")
            except Exception as e:
                print(f"[AUDIT ERROR] Failed to send checklist to Laravel: {e}")

            self.root.after(0, self._finalize_unlock_session)

        threading.Thread(target=send_checklist_to_laravel, daemon=True).start()

    def _finalize_unlock_session(self):
        CinematicNotify(
            self.root,
            "Session Started",
            f"Terminal unlocked. Welcome, {self.current_student['name']}!",
            color="#10b981",
        )
        self.root.after(1000, self.hide_terminal)

    # =================================================================
    # NETWORK MONITOR & WI-FI POPUP
    # =================================================================
    def network_monitor_loop(self):
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
                    self.root.after(0, self.update_net_status, False)
            except Exception as e:
                self.root.after(0, self.update_net_status, False)
            time.sleep(5)

    def update_net_status(self, is_online):
        if is_online:
            self.net_indicator.config(text="● ONLINE", fg="#10b981")
        else:
            self.net_indicator.config(text="▲ OFFLINE (CLICK TO FIX WI-FI)", fg="#ef4444")

    def open_wifi_modal(self):
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
        self.wifi_modal.transient(self.root)

        def close_wifi(event=None):
            if self.wifi_modal and self.wifi_modal.winfo_exists():
                self.wifi_modal.destroy()
                self.wifi_modal = None
            self.root.attributes("-topmost", True)
            if self.login_view.winfo_ismapped():
                self.entry_id.focus_set()

        self.wifi_modal.bind("<Escape>", close_wifi)

        header_frame = tk.Frame(self.wifi_modal, bg="#1e293b")
        header_frame.pack(fill="x", padx=15, pady=(15, 0))

        tk.Label(header_frame, text="NETWORK SETTINGS", fg="#D4AF37", bg="#1e293b", font=("Arial Black", 14)).pack(side="left")

        btn_x = tk.Button(
            header_frame, text=" ✕ ", command=close_wifi,
            bg="#1e293b", fg="#94a3b8", activebackground="#ef4444",
            activeforeground="white", font=("Arial", 12, "bold"), border=0, cursor="hand2",
        )
        btn_x.pack(side="right")

        tk.Label(
            self.wifi_modal, text="Select an available Wi-Fi access point to connect.",
            fg="#94a3b8", bg="#1e293b", font=("Arial", 9),
        ).pack(anchor="w", padx=15, pady=(2, 10))

        list_frame = tk.Frame(self.wifi_modal, bg="#0f172a")
        list_frame.pack(fill="both", expand=True, padx=20, pady=5)

        self.wifi_listbox = tk.Listbox(
            list_frame, bg="#0f172a", fg="white", font=("Arial", 11),
            selectbackground="#D4AF37", borderwidth=0, highlightthickness=0,
        )
        self.wifi_listbox.pack(side="left", fill="both", expand=True, padx=5, pady=5)

        tk.Label(self.wifi_modal, text="Security Key / Password", fg="white", bg="#1e293b", font=("Arial", 9, "bold")).pack(anchor="w", padx=20, pady=(10, 2))

        self.wifi_pass = tk.Entry(self.wifi_modal, font=("Arial", 12), show="*", bg="#0f172a", fg="white", border=0, insertbackground="white")
        self.wifi_pass.pack(fill="x", padx=20, pady=5, ipady=6)

        btn_frame = tk.Frame(self.wifi_modal, bg="#1e293b")
        btn_frame.pack(pady=20)

        tk.Button(
            btn_frame, text="SCAN WI-FI", command=self.scan_wifi_networks,
            bg="#3b82f6", fg="white", font=("Arial", 9, "bold"), width=12, height=2, relief="flat", cursor="hand2",
        ).pack(side="left", padx=5)

        tk.Button(
            btn_frame, text="CONNECT", command=self.connect_to_wifi,
            bg="#10b981", fg="white", font=("Arial", 9, "bold"), width=12, height=2, relief="flat", cursor="hand2",
        ).pack(side="left", padx=5)

        tk.Button(
            btn_frame, text="CLOSE", command=close_wifi,
            bg="#475569", fg="white", font=("Arial", 9, "bold"), width=10, height=2, relief="flat", cursor="hand2",
        ).pack(side="left", padx=5)

        self.wifi_pass.focus_set()
        self.scan_wifi_networks()

    def _set_wifi_list_items(self, items):
        if self.wifi_modal and self.wifi_modal.winfo_exists():
            self.wifi_listbox.delete(0, tk.END)
            for item in items:
                self.wifi_listbox.insert(tk.END, item)

    def scan_wifi_networks(self):
        if self.wifi_modal and self.wifi_modal.winfo_exists():
            self.wifi_listbox.delete(0, tk.END)
            self.wifi_listbox.insert(tk.END, "Turning on Wi-Fi adapter & scanning...")

        def execute_scan():
            enable_wifi_adapter()
            time.sleep(2.5)
            found_ssids = get_native_wifi_networks()
            if not found_ssids:
                try:
                    output = subprocess.check_output("netsh wlan show networks", shell=True, stderr=subprocess.STDOUT).decode("utf-8", errors="ignore")
                    ssids = re.findall(r"SSID\s+\d+\s*:\s*(.+)", output)
                    found_ssids = sorted(list(set([s.strip() for s in ssids if s.strip() and not s.strip().startswith("SSID")])))
                except Exception:
                    pass

            if found_ssids:
                self.root.after(0, lambda: self._set_wifi_list_items(found_ssids))
            else:
                self.root.after(0, lambda: self._set_wifi_list_items(["No networks found. Try scanning again."]))

        threading.Thread(target=execute_scan, daemon=True).start()

    def connect_to_wifi(self):
        try:
            selected_ssid = self.wifi_listbox.get(self.wifi_listbox.curselection())
        except Exception:
            CinematicNotify(self.wifi_modal, "Selection Required", "Please click an SSID from the list.", color="#ef4444")
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

                subprocess.run(f'netsh wlan add profile filename="{filename}"', shell=True, capture_output=True, text=True)
                res_conn = subprocess.run(f'netsh wlan connect name="{selected_ssid}"', shell=True, capture_output=True, text=True)

                if os.path.exists(filename):
                    os.remove(filename)

                if res_conn.returncode == 0:
                    CinematicNotify(self.wifi_modal, "Connecting", f"Connecting to {selected_ssid}...", color="#3b82f6")
                else:
                    CinematicNotify(self.wifi_modal, "Wi-Fi Error", "Could not connect to target network.", color="#ef4444")
            except Exception as e:
                CinematicNotify(self.wifi_modal, "Wi-Fi Error", f"Profile creation failed: {e}", color="#ef4444")

        threading.Thread(target=execute_connection, daemon=True).start()

    # =================================================================
    # FIXED: RELOCATED & STREAMLINED ISSUE REPORT OVERLAY
    # Uses ttk.Combobox (state=readonly) & modal transient to prevent auto-close
    # =================================================================
    def open_report_overlay(self, prefill=False):
        if self.overlay and self.overlay.winfo_exists():
            self.overlay.lift()
            return

        self.root.attributes("-topmost", False)
        self.overlay = tk.Toplevel(self.root)
        self.overlay.configure(bg="#1e293b")
        self.overlay.overrideredirect(True)

        width = 480
        height = 490 if prefill else 640

        screen_w = self.root.winfo_screenwidth()
        screen_h = self.root.winfo_screenheight()
        x = (screen_w // 2) - (width // 2)
        y = (screen_h // 2) - (height // 2)
        self.overlay.geometry(f"{width}x{height}+{x}+{y}")
        self.overlay.attributes("-topmost", True)
        
        # Make modal transient without grab_set conflict (fixes dropdown closing bug)
        self.overlay.transient(self.root)

        tk.Label(self.overlay, text="REPORT A PROBLEM", fg="#ef4444", bg="#1e293b", font=("Arial Black", 16)).pack(pady=(22, 2))
        tk.Label(
            self.overlay,
            text=f"Logging an issue for terminal {PC_NUMBER}",
            fg="#94a3b8",
            bg="#1e293b",
            font=("Arial", 9),
        ).pack(pady=(0, 8))

        self.report_student_id = tk.Entry(self.overlay)
        self.report_password = tk.Entry(self.overlay)

        if prefill and self.current_student["id"]:
            self.report_student_id.insert(0, self.current_student["id"])
            self.report_password.insert(0, self.current_student["password"])

            identity_pill = tk.Frame(self.overlay, bg="#0f172a", highlightthickness=1, highlightbackground="#334155")
            identity_pill.pack(fill="x", padx=50, pady=(6, 12))

            tk.Label(
                identity_pill,
                text=f"🛡️ Verified Identity: {self.current_student['name']} ({self.current_student['id']})",
                fg="#10b981",
                bg="#0f172a",
                font=("Arial", 9, "bold"),
                padx=10,
                pady=8,
            ).pack()
        else:
            tk.Label(self.overlay, text="Student Number / ID", fg="white", bg="#1e293b", font=("Arial", 9, "bold")).pack(anchor="w", padx=50, pady=(10, 2))
            self.report_student_id = tk.Entry(self.overlay, font=("Arial", 11), bg="#0f172a", fg="white", border=0, insertbackground="white")
            self.report_student_id.pack(fill="x", padx=50, ipady=6)
            self.report_student_id.bind("<Key>", self._filter_student_id_key)
            self.report_student_id.bind("<KeyRelease>", self._format_student_id_entry)

            tk.Label(self.overlay, text="Account Password", fg="white", bg="#1e293b", font=("Arial", 9, "bold")).pack(anchor="w", padx=50, pady=(10, 2))
            self.report_password = tk.Entry(self.overlay, font=("Arial", 11), show="*", bg="#0f172a", fg="white", border=0, insertbackground="white")
            self.report_password.pack(fill="x", padx=50, ipady=6)

        tk.Label(self.overlay, text="Problem Category", fg="white", bg="#1e293b", font=("Arial", 9, "bold")).pack(anchor="w", padx=50, pady=(6, 2))

        # Replaced tk.OptionMenu with ttk.Combobox (Fixes dropdown instantly closing!)
        categories = [
            "Missing / Faulty Hardware",
            "System Unit / Tower Issue",
            "Monitor Defective / Cracked",
            "Power Unit / AVR Failure",
            "Optical Mouse Unresponsive",
            "Keyboard Damaged / Missing Keys",
            "Loose / Damaged Cables",
            "No Internet / Wi-Fi Problem",
            "Other Terminal Concern"
        ]
        
        self.issue_var = tk.StringVar(value=categories[0])
        
        # Configure dark styling for Combobox
        style = ttk.Style()
        style.theme_use('clam')
        style.configure("Dark.TCombobox", 
            fieldbackground="#0f172a",
            background="#1e293b",
            foreground="white",
            darkcolor="#334155",
            lightcolor="#334155",
            bordercolor="#334155",
            arrowcolor="#D4AF37",
            selectbackground="#D4AF37",
            selectforeground="black"
        )
        
        self.combo_category = ttk.Combobox(
            self.overlay,
            textvariable=self.issue_var,
            values=categories,
            state="readonly",
            style="Dark.TCombobox",
            font=("Arial", 10)
        )
        self.combo_category.pack(fill="x", padx=50, ipady=4)

        tk.Label(self.overlay, text="Describe the Problem", fg="white", bg="#1e293b", font=("Arial", 9, "bold")).pack(anchor="w", padx=50, pady=(10, 2))
        self.remarks_box = tk.Text(self.overlay, height=4, font=("Arial", 10), bg="#0f172a", fg="white", border=0, padx=12, pady=8, insertbackground="white")
        self.remarks_box.pack(padx=50, fill="x")

        def close_overlay():
            if self.overlay and self.overlay.winfo_exists():
                self.overlay.destroy()
                self.overlay = None
            self.root.attributes("-topmost", True)

        def handle_submit():
            student_id = self.current_student["id"] if prefill else self.report_student_id.get().strip()
            password = self.current_student["password"] if prefill else self.report_password.get()
            remarks = self.remarks_box.get("1.0", tk.END).strip()

            if not student_id or not password:
                CinematicNotify(self.overlay, "Identity Required", "Credentials required to verify report authenticity.", color="#ef4444")
                return
            if not remarks:
                CinematicNotify(self.overlay, "Incomplete", "Please describe the hardware or station issue.", color="#ef4444")
                return

            self.btn_send.config(state="disabled", text="DISPATCHING...")

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
                    response = session.post(f"{API_URL}/alerts", json=payload, timeout=8)

                    if response.status_code in [200, 201]:
                        self.root.after(0, lambda: CinematicNotify(self.root, "Report Submitted", "Your ticket has been sent to technical support.", color="#10b981"))
                        self.root.after(0, close_overlay)
                    else:
                        msg = response.json().get("message", "Authentication check failed.")
                        self.root.after(0, lambda: CinematicNotify(self.overlay, "Auth Failure", msg, color="#ef4444"))
                        self.root.after(0, lambda: self.btn_send.config(state="normal", text="SUBMIT REPORT"))
                except Exception:
                    self.root.after(0, lambda: CinematicNotify(self.overlay, "Connection Error", "Could not dispatch report to server.", color="#ef4444"))
                    self.root.after(0, lambda: self.btn_send.config(state="normal", text="SUBMIT REPORT"))

            threading.Thread(target=async_report, daemon=True).start()

        btn_container = tk.Frame(self.overlay, bg="#1e293b")
        btn_container.pack(pady=18)

        self.btn_send = tk.Button(
            btn_container, text="SUBMIT REPORT", command=handle_submit,
            bg="#ef4444", fg="white", font=("Arial", 9, "bold"), width=16, height=2, relief="flat", cursor="hand2",
        )
        self.btn_send.pack(side="left", padx=6)

        tk.Button(
            btn_container, text="CANCEL", command=close_overlay,
            bg="#475569", fg="white", font=("Arial", 9, "bold"), width=12, height=2, relief="flat", cursor="hand2",
        ).pack(side="left", padx=6)

    def force_on_top(self):
        if not self.is_session_active:
            if self.wifi_modal and self.wifi_modal.winfo_exists():
                self.wifi_modal.lift()
                self.wifi_modal.attributes("-topmost", True)
            elif self.overlay and self.overlay.winfo_exists():
                # Lift overlay without aggressive grabbing so dropdown menus stay open
                self.overlay.lift()
                self.overlay.attributes("-topmost", True)
            else:
                self.root.lift()
                self.root.attributes("-topmost", True)
        self.root.after(1000, self.force_on_top)

    # =================================================================
    # AUTHENTICATION & RBAC DISPATCHER
    # =================================================================
    def attempt_login(self):
        login_credential = self.entry_id.get().strip()
        password = self.entry_password.get()

        if not login_credential or not password:
            CinematicNotify(self.root, "Input Required", "Enter your ID and account password.", color="#D4AF37")
            return

        self.btn_unlock.config(state="disabled", text="VERIFYING...")
        payload = {
            "pc_number": PC_NUMBER,
            "lab": LAB_ID,
            "student_id": login_credential,
            "password": password,
        }

        def perform_login():
            try:
                session = get_authenticated_session()
                response = session.post(f"{API_URL}/login", json=payload, timeout=10)
                if response.status_code == 200:
                    resp_json = response.json()
                    user_name = resp_json.get("name", "User")
                    user_role = str(resp_json.get("role") or resp_json.get("data", {}).get("role", "student")).lower()
                    session_id = resp_json.get("session_id") or resp_json.get("data", {}).get("session_id")

                    self.current_student = {
                        "id": login_credential,
                        "password": password,
                        "name": user_name,
                        "role": user_role,
                        "session_id": session_id,
                    }

                    # RBAC: Staff bypasses inspection and unlocks directly
                    if user_role in ["admin", "super-admin", "personnel", "teacher", "technician", "faculty", "staff"]:
                        print(f"[RBAC] Staff role '{user_role}' authenticated. Direct unlock granted.")
                        self.root.after(0, lambda: CinematicNotify(self.root, "Staff Access", f"Welcome, {user_name} ({user_role.upper()})!", color="#10b981"))
                        self.root.after(1000, self.hide_terminal)
                    else:
                        # Students proceed to Page 2 (Inspection Audit)
                        self.root.after(0, lambda: self.show_checklist_screen(user_name))
                else:
                    try:
                        msg = response.json().get("message", "Invalid Credentials.")
                    except Exception:
                        msg = f"HTTP Error {response.status_code}"
                    self.root.after(0, lambda: CinematicNotify(self.root, "Auth Failed", msg, color="#ef4444"))
                    self.root.after(0, lambda: self.btn_unlock.config(state="normal", text="AUTHENTICATE & INSPECT"))
            except Exception as e:
                self.root.after(0, lambda: CinematicNotify(self.root, "Error", "Server unreachable. Check connection.", color="#ef4444"))
                self.root.after(0, lambda: self.btn_unlock.config(state="normal", text="AUTHENTICATE & INSPECT"))

        threading.Thread(target=perform_login, daemon=True).start()

    # =================================================================
    # SESSION LIFECYCLE, TRAY ICON & SIGN-OUT BAR
    # =================================================================
    def hide_terminal(self):
        self.is_session_active = True
        self.root.attributes("-topmost", False)

        show_taskbar()
        stop_keyboard_hook()

        self.root.withdraw()
        self.start_system_tray()
        self.show_floating_signout_pill()
        threading.Thread(target=self.heartbeat_loop, daemon=True).start()

    def create_tray_image(self):
        img = Image.new("RGBA", (64, 64), color=(0, 0, 0, 0))
        draw = ImageDraw.Draw(img)
        draw.ellipse([4, 4, 60, 60], fill="#0f172a", outline="#D4AF37", width=3)
        draw.rectangle([22, 22, 42, 42], fill="#10b981")
        return img

    def start_system_tray(self):
        if not TRAY_AVAILABLE:
            print("[NOTICE] pystray/PIL not installed. Desktop floating sign-out pill active.")
            return

        def on_signout_click(icon, item):
            self.root.after(0, self.request_manual_logout)

        menu = pystray.Menu(
            pystray.MenuItem(f"Station: {PC_NUMBER}", lambda: None, enabled=False),
            pystray.MenuItem(f"User: {self.current_student['name']} ({self.current_student['role'].upper()})", lambda: None, enabled=False),
            pystray.Menu.SEPARATOR,
            pystray.MenuItem("Sign Out & Lock PC", on_signout_click, default=True),
        )

        try:
            self.tray_icon = pystray.Icon("LabGuard", self.create_tray_image(), f"LabGuard ({PC_NUMBER})", menu)
            threading.Thread(target=self.tray_icon.run, daemon=True).start()
        except Exception as e:
            print(f"[DEBUG] Tray creation error: {e}")

    def stop_system_tray(self):
        if self.tray_icon:
            try:
                self.tray_icon.stop()
            except Exception:
                pass
            self.tray_icon = None

    def show_floating_signout_pill(self):
        if self.floating_pill and self.floating_pill.winfo_exists():
            return

        self.floating_pill = tk.Toplevel()
        self.floating_pill.overrideredirect(True)
        self.floating_pill.attributes("-topmost", True)
        self.floating_pill.configure(bg="#0f172a")

        screen_w = self.root.winfo_screenwidth()
        p_w, p_h = 240, 42
        x = screen_w - p_w - 20
        y = 15
        self.floating_pill.geometry(f"{p_w}x{p_h}+{x}+{y}")

        container = tk.Frame(self.floating_pill, bg="#1e293b", highlightbackground="#D4AF37", highlightthickness=1)
        container.pack(fill="both", expand=True)

        role_label = f"● {PC_NUMBER}"
        tk.Label(container, text=role_label, fg="#10b981", bg="#1e293b", font=("Arial", 9, "bold")).pack(side="left", padx=(10, 5))

        btn = tk.Button(
            container,
            text="SIGN OUT",
            command=self.request_manual_logout,
            bg="#ef4444",
            fg="white",
            font=("Arial", 8, "bold"),
            relief="flat",
            padx=10,
            cursor="hand2",
        )
        btn.pack(side="right", padx=8, pady=6)

    def hide_floating_signout_pill(self):
        if self.floating_pill and self.floating_pill.winfo_exists():
            self.floating_pill.destroy()
            self.floating_pill = None

    def request_manual_logout(self):
        send_logout_signal()
        self.lock_ui_again()

    def heartbeat_loop(self):
        time.sleep(10)
        consecutive_failures = 0

        while self.is_session_active:
            try:
                lab_param = urllib.parse.quote(str(LAB_ID))
                pc_param = urllib.parse.quote(str(PC_NUMBER))
                url = f"{API_URL}/status/{lab_param}/{pc_param}"

                session = get_authenticated_session()
                response = session.get(url, timeout=5)

                if response.status_code == 200:
                    consecutive_failures = 0
                    data = response.json()
                    pc_status = data.get("status") or data.get("data", {}).get("status")

                    if pc_status and str(pc_status).lower() in ["released", "maintenance"]:
                        self.root.after(0, self.lock_ui_again)
                        break
            except Exception as e:
                consecutive_failures += 1
                if consecutive_failures >= 5:
                    self.root.after(0, self.lock_ui_again)
                    break

            time.sleep(5)

    def lock_ui_again(self):
        self.is_session_active = False

        self.stop_system_tray()
        self.hide_floating_signout_pill()

        self.current_student = {"id": "", "password": "", "name": "", "role": "student", "session_id": None}
        self.entry_id.delete(0, tk.END)
        self.entry_password.delete(0, tk.END)
        self.btn_unlock.config(state="normal", text="AUTHENTICATE & INSPECT")

        self.checklist_view.pack_forget()
        self.login_view.pack()

        hide_taskbar()
        start_keyboard_hook()

        self.root.deiconify()
        self.root.lift()
        self.root.attributes("-topmost", True)
        self.entry_id.focus_set()

    def emergency_admin_exit(self, event=None):
        print("[ADMIN] Emergency exit invoked. Restoring full workstation control...")
        self.stop_system_tray()
        self.hide_floating_signout_pill()
        cleanup_security()
        self.root.destroy()
        sys.exit(0)


# =====================================================================
# 8. APPLICATION ENTRYPOINT
# =====================================================================
if __name__ == "__main__":
    app_root = tk.Tk()
    client = LabGuardClient(app_root)
    app_root.update()
    app_root.mainloop()