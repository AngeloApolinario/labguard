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

try:
    import pystray
    from PIL import Image, ImageDraw
    TRAY_AVAILABLE = True
except ImportError:
    TRAY_AVAILABLE = False

urllib3.disable_warnings(
    urllib3.exceptions.InsecureRequestWarning
)


# =====================================================================
# 1. WIN32 API CONSTANTS & STRUCTURES
# =====================================================================

WH_KEYBOARD_LL = 13

VK_TAB = 0x09
VK_ESCAPE = 0x1B
VK_CONTROL = 0x11
VK_MENU = 0x12
VK_LWIN = 0x5B
VK_RWIN = 0x5C
VK_F4 = 0x73

ULONG_PTR = (
    ctypes.c_ulonglong
    if ctypes.sizeof(ctypes.c_void_p) == 8
    else ctypes.c_ulong
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
_hook_proc_ref = None

user32 = ctypes.windll.user32
kernel32 = ctypes.windll.kernel32

HOOKPROC = ctypes.WINFUNCTYPE(
    LRESULT,
    ctypes.c_int,
    wintypes.WPARAM,
    wintypes.LPARAM
)

user32.CallNextHookEx.argtypes = [
    wintypes.HANDLE,
    ctypes.c_int,
    wintypes.WPARAM,
    wintypes.LPARAM
]

user32.CallNextHookEx.restype = LRESULT

user32.SetWindowsHookExW.argtypes = [
    ctypes.c_int,
    HOOKPROC,
    wintypes.HINSTANCE,
    wintypes.DWORD
]

user32.SetWindowsHookExW.restype = wintypes.HANDLE

user32.UnhookWindowsHookEx.argtypes = [
    wintypes.HANDLE
]

user32.UnhookWindowsHookEx.restype = wintypes.BOOL


# =====================================================================
# 2. TASKBAR CONTROL
# =====================================================================

def hide_taskbar():
    try:
        hwnd_primary = user32.FindWindowW(
            "Shell_TrayWnd",
            None
        )

        if hwnd_primary:
            user32.ShowWindow(
                hwnd_primary,
                0
            )

            user32.EnableWindow(
                hwnd_primary,
                False
            )

        hwnd_secondary = user32.FindWindowW(
            "Shell_SecondaryTrayWnd",
            None
        )

        if hwnd_secondary:
            user32.ShowWindow(
                hwnd_secondary,
                0
            )

            user32.EnableWindow(
                hwnd_secondary,
                False
            )

        print("[DEBUG] Taskbar hidden.")

    except Exception as e:
        print(
            f"[DEBUG] Error hiding taskbar: {e}"
        )


def show_taskbar():
    try:
        hwnd_primary = user32.FindWindowW(
            "Shell_TrayWnd",
            None
        )

        if hwnd_primary:
            user32.EnableWindow(
                hwnd_primary,
                True
            )

            user32.ShowWindow(
                hwnd_primary,
                5
            )

        hwnd_secondary = user32.FindWindowW(
            "Shell_SecondaryTrayWnd",
            None
        )

        if hwnd_secondary:
            user32.EnableWindow(
                hwnd_secondary,
                True
            )

            user32.ShowWindow(
                hwnd_secondary,
                5
            )

        print("[DEBUG] Taskbar restored.")

    except Exception as e:
        print(
            f"[DEBUG] Error showing taskbar: {e}"
        )


# =====================================================================
# 3. LOW-LEVEL KEYBOARD HOOK
# =====================================================================

def _low_level_keyboard_proc(
    nCode,
    wParam,
    lParam
):
    try:
        if nCode >= 0 and lParam:
            kb_struct = KBDLLHOOKSTRUCT.from_address(
                lParam
            )

            vk_code = kb_struct.vkCode

            if vk_code not in (
                VK_LWIN,
                VK_RWIN,
                VK_TAB,
                VK_ESCAPE,
                VK_F4
            ):
                return user32.CallNextHookEx(
                    _hook_id,
                    nCode,
                    wParam,
                    lParam
                )

            flags = kb_struct.flags

            is_alt_pressed = (
                bool(flags & 0x20)
                or (
                    user32.GetAsyncKeyState(
                        VK_MENU
                    ) & 0x8000
                ) != 0
            )

            if vk_code in (
                VK_LWIN,
                VK_RWIN
            ):
                return 1

            if (
                vk_code == VK_TAB
                and is_alt_pressed
            ):
                return 1

            if (
                vk_code == VK_ESCAPE
                and is_alt_pressed
            ):
                return 1

            if vk_code == VK_ESCAPE:
                is_ctrl_pressed = (
                    user32.GetAsyncKeyState(
                        VK_CONTROL
                    ) & 0x8000
                ) != 0

                if is_ctrl_pressed:
                    return 1

            if (
                vk_code == VK_F4
                and is_alt_pressed
            ):
                return 1

    except Exception as e:
        print(
            f"[DEBUG] Hook Procedure Error: {e}"
        )

    return user32.CallNextHookEx(
        _hook_id,
        nCode,
        wParam,
        lParam
    )


def start_keyboard_hook():
    global _hook_id
    global _hook_proc_ref

    if _hook_id is not None:
        return

    try:
        _hook_proc_ref = HOOKPROC(
            _low_level_keyboard_proc
        )

        _hook_id = user32.SetWindowsHookExW(
            WH_KEYBOARD_LL,
            _hook_proc_ref,
            None,
            0
        )

        if not _hook_id or _hook_id == 0:
            err = kernel32.GetLastError()

            print(
                f"[DEBUG] SetWindowsHookExW FAILED: {err}"
            )

            _hook_id = None

        else:
            print(
                f"[DEBUG] Keyboard Hook installed! "
                f"Hook ID: {_hook_id}"
            )

    except Exception as e:
        print(
            f"[DEBUG] Keyboard Hook exception: {e}"
        )


def stop_keyboard_hook():
    global _hook_id

    if _hook_id:
        try:
            user32.UnhookWindowsHookEx(
                _hook_id
            )

            print(
                "[DEBUG] Keyboard Hook uninstalled."
            )

        except Exception as e:
            print(
                f"[DEBUG] Error uninstalling hook: {e}"
            )

        _hook_id = None


# =====================================================================
# 4. CONFIGURATION & SANCTUM SESSION
# =====================================================================

def load_config():
    if getattr(sys, "frozen", False):
        base_dir = os.path.dirname(
            sys.executable
        )
    else:
        base_dir = os.path.dirname(
            os.path.abspath(__file__)
        )

    config_path = os.path.join(
        base_dir,
        "config.json"
    )

    config_data = {
        "server_url": "https://labguard.test/api/pc",
        "lab": "LAB 1",
        "pc": "PC-01",
    }

    if os.path.exists(config_path):
        try:
            with open(
                config_path,
                "r",
                encoding="utf-8"
            ) as f:
                config_data = json.load(f)

        except Exception as e:
            print(
                f"Error loading config.json: {e}"
            )

    return config_data


config = load_config()

API_URL = config.get(
    "server_url",
    "https://labguard.it.com/api/pc"
).rstrip("/")

if not API_URL.endswith("/api/pc"):
    API_URL += "/api/pc"

LAB_ID = str(
    config.get(
        "lab",
        "LAB 1"
    )
).strip()

PC_NUMBER = str(
    config.get(
        "pc",
        "PC-01"
    )
).strip()

HEADERS = {
    "Accept": "application/json"
}


def get_authenticated_session():
    session = requests.Session()

    session.verify = False

    base_domain = API_URL.split(
        "/api"
    )[0]

    try:
        session.get(
            f"{base_domain}/sanctum/csrf-cookie",
            headers=HEADERS,
            timeout=5
        )

        csrf_token = session.cookies.get(
            "XSRF-TOKEN"
        )

        if csrf_token:
            session.headers.update({
                "X-XSRF-TOKEN":
                    urllib.parse.unquote(
                        csrf_token
                    ),
                "Accept":
                    "application/json"
            })

    except Exception as e:
        print(
            f"[DEBUG] CSRF Cookie Fetch Exception: {e}"
        )

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
            json={
                "pc_number": PC_NUMBER
            },
            timeout=3,
        )

        print(
            f"Signal Sent: {PC_NUMBER} has been released."
        )

    except Exception as e:
        print(
            f"Logout signal failed: {e}"
        )


def handle_exit_signal(
    sig,
    frame
):
    print(
        "Force shutdown detected..."
    )

    send_logout_signal()

    sys.exit(0)


signal.signal(
    signal.SIGINT,
    handle_exit_signal
)

signal.signal(
    signal.SIGTERM,
    handle_exit_signal
)

atexit.register(
    cleanup_security
)


def register_shutdown_hooks():
    def windows_shutdown_handler(
        ctrl_type
    ):
        if ctrl_type in (
            2,
            5,
            6
        ):
            print(
                "[SHUTDOWN] Windows OS "
                "Shutdown/Logoff detected! "
                "Releasing PC..."
            )

            send_logout_signal()

            return True

        return False

    try:
        PHANDLER_ROUTINE = ctypes.WINFUNCTYPE(
            wintypes.BOOL,
            wintypes.DWORD
        )

        handler_delegate = PHANDLER_ROUTINE(
            windows_shutdown_handler
        )

        register_shutdown_hooks.handler_delegate = (
            handler_delegate
        )

        kernel32.SetConsoleCtrlHandler(
            handler_delegate,
            True
        )

    except Exception as e:
        print(
            f"Could not register Windows "
            f"shutdown hook: {e}"
        )


# =====================================================================
# 5. WI-FI MANAGEMENT
# =====================================================================

def turn_on_wifi_radio_native():
    try:
        wlanapi = ctypes.windll.wlanapi

        class GUID(ctypes.Structure):
            _fields_ = [
                ("Data1", wintypes.DWORD),
                ("Data2", wintypes.WORD),
                ("Data3", wintypes.WORD),
                (
                    "Data4",
                    wintypes.BYTE * 8
                ),
            ]

        class WLAN_INTERFACE_INFO(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "InterfaceGuid",
                    GUID
                ),
                (
                    "strInterfaceDescription",
                    wintypes.WCHAR * 256
                ),
                (
                    "isState",
                    ctypes.c_uint
                ),
            ]

        class WLAN_INTERFACE_INFO_LIST(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "dwNumberOfItems",
                    wintypes.DWORD
                ),
                (
                    "dwIndex",
                    wintypes.DWORD
                ),
                (
                    "InterfaceInfo",
                    WLAN_INTERFACE_INFO * 1
                ),
            ]

        class WLAN_PHY_RADIO_STATE(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "dwPhyIndex",
                    wintypes.DWORD
                ),
                (
                    "dot11SoftwareRadioState",
                    ctypes.c_uint
                ),
                (
                    "dot11HardwareRadioState",
                    ctypes.c_uint
                ),
            ]

        hClient = wintypes.HANDLE()
        pVersion = wintypes.DWORD()

        if wlanapi.WlanOpenHandle(
            2,
            None,
            ctypes.byref(pVersion),
            ctypes.byref(hClient)
        ) == 0:

            pList = ctypes.POINTER(
                WLAN_INTERFACE_INFO_LIST
            )()

            if wlanapi.WlanEnumInterfaces(
                hClient,
                None,
                ctypes.byref(pList)
            ) == 0:

                if (
                    pList.contents.dwNumberOfItems
                    > 0
                ):
                    guid = (
                        pList.contents
                        .InterfaceInfo[0]
                        .InterfaceGuid
                    )

                    radio_state = (
                        WLAN_PHY_RADIO_STATE(
                            0,
                            1,
                            1
                        )
                    )

                    wlanapi.WlanSetInterface(
                        hClient,
                        ctypes.byref(guid),
                        4,
                        ctypes.sizeof(
                            WLAN_PHY_RADIO_STATE
                        ),
                        ctypes.byref(
                            radio_state
                        ),
                        None
                    )

                    wlanapi.WlanScan(
                        hClient,
                        ctypes.byref(guid),
                        None,
                        None,
                        None
                    )

                wlanapi.WlanFreeMemory(
                    pList
                )

            wlanapi.WlanCloseHandle(
                hClient,
                None
            )

            return True

    except Exception as e:
        print(
            f"Native radio activation failed: {e}"
        )

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
                (
                    "Data4",
                    wintypes.BYTE * 8
                ),
            ]

        class WLAN_INTERFACE_INFO(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "InterfaceGuid",
                    GUID
                ),
                (
                    "strInterfaceDescription",
                    wintypes.WCHAR * 256
                ),
                (
                    "isState",
                    ctypes.c_uint
                ),
            ]

        class WLAN_INTERFACE_INFO_LIST(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "dwNumberOfItems",
                    wintypes.DWORD
                ),
                (
                    "dwIndex",
                    wintypes.DWORD
                ),
                (
                    "InterfaceInfo",
                    WLAN_INTERFACE_INFO * 1
                ),
            ]

        class DOT11_SSID(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "uSSIDLength",
                    ctypes.c_ulong
                ),
                (
                    "ucSSID",
                    ctypes.c_char * 32
                ),
            ]

        class WLAN_AVAILABLE_NETWORK(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "strProfileName",
                    wintypes.WCHAR * 256
                ),
                (
                    "dot11Ssid",
                    DOT11_SSID
                ),
                (
                    "dot11BssType",
                    ctypes.c_uint
                ),
                (
                    "uNumberOfBssids",
                    ctypes.c_ulong
                ),
                (
                    "bNetworkConnectable",
                    wintypes.BOOL
                ),
                (
                    "wlanNotConnectableReason",
                    wintypes.DWORD
                ),
                (
                    "uNumberOfPhyTypes",
                    ctypes.c_ulong
                ),
                (
                    "dot11PhyTypes",
                    ctypes.c_uint * 8
                ),
                (
                    "bMorePhyTypes",
                    wintypes.BOOL
                ),
                (
                    "wlanSignalQuality",
                    ctypes.c_ulong
                ),
                (
                    "bSecurityEnabled",
                    wintypes.BOOL
                ),
                (
                    "dot11DefaultAuthAlgorithm",
                    ctypes.c_uint
                ),
                (
                    "dot11DefaultCipherAlgorithm",
                    ctypes.c_uint
                ),
                (
                    "dwFlags",
                    wintypes.DWORD
                ),
                (
                    "dwReserved",
                    wintypes.DWORD
                ),
            ]

        class WLAN_AVAILABLE_NETWORK_LIST(
            ctypes.Structure
        ):
            _fields_ = [
                (
                    "dwNumberOfItems",
                    wintypes.DWORD
                ),
                (
                    "dwIndex",
                    wintypes.DWORD
                ),
                (
                    "Network",
                    WLAN_AVAILABLE_NETWORK * 1
                ),
            ]

        hClient = wintypes.HANDLE()
        pVersion = wintypes.DWORD()

        if wlanapi.WlanOpenHandle(
            2,
            None,
            ctypes.byref(pVersion),
            ctypes.byref(hClient)
        ) == 0:

            pList = ctypes.POINTER(
                WLAN_INTERFACE_INFO_LIST
            )()

            if wlanapi.WlanEnumInterfaces(
                hClient,
                None,
                ctypes.byref(pList)
            ) == 0:

                if (
                    pList.contents.dwNumberOfItems
                    > 0
                ):
                    guid = (
                        pList.contents
                        .InterfaceInfo[0]
                        .InterfaceGuid
                    )

                    pNetList = ctypes.POINTER(
                        WLAN_AVAILABLE_NETWORK_LIST
                    )()

                    if (
                        wlanapi
                        .WlanGetAvailableNetworkList(
                            hClient,
                            ctypes.byref(guid),
                            2,
                            None,
                            ctypes.byref(
                                pNetList
                            )
                        ) == 0
                    ):

                        num_items = (
                            pNetList.contents
                            .dwNumberOfItems
                        )

                        base_ptr = (
                            ctypes.addressof(
                                pNetList
                                .contents
                                .Network
                            )
                        )

                        stride = ctypes.sizeof(
                            WLAN_AVAILABLE_NETWORK
                        )

                        for i in range(
                            num_items
                        ):
                            net = (
                                WLAN_AVAILABLE_NETWORK
                                .from_address(
                                    base_ptr
                                    + i * stride
                                )
                            )

                            ssid_len = (
                                net.dot11Ssid
                                .uSSIDLength
                            )

                            if (
                                0 < ssid_len <= 32
                            ):
                                ssid_bytes = bytes(
                                    net.dot11Ssid
                                    .ucSSID[
                                        :ssid_len
                                    ]
                                )

                                ssid_str = (
                                    ssid_bytes
                                    .decode(
                                        "utf-8",
                                        errors="ignore"
                                    )
                                    .strip()
                                )

                                if ssid_str:
                                    ssids.append(
                                        ssid_str
                                    )

                        wlanapi.WlanFreeMemory(
                            pNetList
                        )

                wlanapi.WlanFreeMemory(
                    pList
                )

            wlanapi.WlanCloseHandle(
                hClient,
                None
            )

    except Exception as e:
        print(
            f"Native network list failed: {e}"
        )

    return sorted(
        list(
            set(ssids)
        )
    )


def enable_wifi_adapter():
    try:
        subprocess.run(
            [
                "powershell",
                "-NoProfile",
                "-Command",
                "Get-NetAdapter | "
                "Where-Object { $_.Name -match "
                "'Wi-Fi|Wireless|WiFi' } | "
                "Enable-NetAdapter "
                "-Confirm:$false",
            ],
            capture_output=True,
            text=True,
            check=False,
            creationflags=(
                subprocess.CREATE_NO_WINDOW
                if hasattr(
                    subprocess,
                    "CREATE_NO_WINDOW"
                )
                else 0
            ),
        )

    except Exception:
        pass

    turn_on_wifi_radio_native()

    return True, "Wi-Fi enabled."


# =====================================================================
# 6. CINEMATIC NOTIFICATION
# =====================================================================

class CinematicNotify(tk.Toplevel):

    DISPLAY_TIME = 15000
    FADE_STEP = 0.025
    FADE_INTERVAL = 60
    PROGRESS_INTERVAL = 50

    def __init__(
        self,
        parent,
        title,
        message,
        color="#D4AF37",
        position="center",
        on_close=None
    ):
        super().__init__()

        self.parent_reference = parent
        self.on_close_callback = on_close
        self.destroyed = False

        self.overrideredirect(True)

        self.attributes(
            "-topmost",
            True
        )

        self.attributes(
            "-alpha",
            1.0
        )

        self.configure(
            bg="#1e293b",
            highlightbackground=color,
            highlightthickness=2
        )

        try:
            screen_w = self.winfo_screenwidth()
            screen_h = self.winfo_screenheight()

        except Exception:
            screen_w = 1920
            screen_h = 1080

        self.width = 460
        self.height = 190

        if position == "top_left":
            x = 25
            y = 25

        elif position == "top_right":
            x = (
                screen_w
                - self.width
                - 25
            )
            y = 25

        elif position == "bottom_left":
            x = 25
            y = (
                screen_h
                - self.height
                - 25
            )

        elif position == "bottom_right":
            x = (
                screen_w
                - self.width
                - 25
            )
            y = (
                screen_h
                - self.height
                - 25
            )

        else:
            x = (
                screen_w
                // 2
            ) - (
                self.width
                // 2
            )

            y = (
                screen_h
                // 2
            ) - (
                self.height
                // 2
            )

        self.geometry(
            f"{self.width}x{self.height}+{x}+{y}"
        )

        self.title_label = tk.Label(
            self,
            text=title.upper(),
            fg=color,
            bg="#1e293b",
            font=("Arial Black", 15),
            cursor="hand2"
        )

        self.title_label.pack(
            pady=(24, 6)
        )

        self.message_label = tk.Label(
            self,
            text=message,
            fg="white",
            bg="#1e293b",
            font=("Arial", 10),
            wraplength=390,
            justify="center",
            cursor="hand2"
        )

        self.message_label.pack(
            pady=5
        )

        self.dismiss_label = tk.Label(
            self,
            text="Click anywhere to dismiss",
            fg="#64748b",
            bg="#1e293b",
            font=("Arial", 8),
            cursor="hand2"
        )

        self.dismiss_label.pack(
            pady=(6, 0)
        )

        self.progress_bg = tk.Frame(
            self,
            bg="#0f172a",
            height=5
        )

        self.progress_bg.pack(
            side="bottom",
            fill="x"
        )

        self.progress_fill = tk.Frame(
            self.progress_bg,
            bg=color,
            height=5
        )

        self.progress_fill.place(
            x=0,
            y=0,
            width=self.width,
            height=5
        )

        self.bind(
            "<Button-1>",
            self.dismiss
        )

        for widget in (
            self.title_label,
            self.message_label,
            self.dismiss_label,
            self.progress_bg,
            self.progress_fill
        ):
            widget.bind(
                "<Button-1>",
                self.dismiss
            )

        self.protocol(
            "WM_DELETE_WINDOW",
            self.dismiss
        )

        self.lift()

        self.attributes(
            "-topmost",
            True
        )

        self.start_time = time.monotonic()

        self.after(
            self.PROGRESS_INTERVAL,
            self.update_progress
        )

        self.after(
            self.DISPLAY_TIME,
            self.fade_out
        )

    def _run_close_callback(self):
        if self.on_close_callback:
            callback = self.on_close_callback
            self.on_close_callback = None

            try:
                callback()

            except Exception as e:
                print(
                    f"[NOTIFICATION CALLBACK ERROR] {e}"
                )

    def dismiss(
        self,
        event=None
    ):
        if self.destroyed:
            return

        self.destroyed = True

        self._run_close_callback()

        try:
            self.destroy()

        except tk.TclError:
            pass

        except Exception:
            pass

    def update_progress(self):
        if self.destroyed:
            return

        try:
            if not self.winfo_exists():
                self.destroyed = True
                return

            elapsed = (
                time.monotonic()
                - self.start_time
            ) * 1000

            remaining_ratio = max(
                0.0,
                1.0
                - (
                    elapsed
                    / self.DISPLAY_TIME
                )
            )

            current_width = int(
                self.width
                * remaining_ratio
            )

            self.progress_fill.place(
                x=0,
                y=0,
                width=current_width,
                height=5
            )

            if remaining_ratio > 0:
                self.after(
                    self.PROGRESS_INTERVAL,
                    self.update_progress
                )

        except tk.TclError:
            self.destroyed = True

        except Exception:
            self.destroyed = True

    def fade_out(self):
        if self.destroyed:
            return

        try:
            if not self.winfo_exists():
                self.destroyed = True
                return

            alpha = float(
                self.attributes("-alpha")
            )

            alpha -= self.FADE_STEP

            if alpha <= 0:
                self.destroyed = True

                self._run_close_callback()

                self.destroy()

                return

            self.attributes(
                "-alpha",
                alpha
            )

            self.after(
                self.FADE_INTERVAL,
                self.fade_out
            )

        except tk.TclError:
            self.destroyed = True

        except Exception:
            self.destroyed = True

            self._run_close_callback()

            try:
                self.destroy()

            except Exception:
                pass


# =====================================================================
# 7. MAIN LABGUARD TERMINAL CLIENT
# =====================================================================

class LabGuardClient:

    def __init__(
        self,
        root
    ):
        self.root = root

        self.is_session_active = False
        self.is_maintenance_mode = False

        self.wifi_modal = None
        self.wifi_active = False
        self.shutdown_modal = None

        self.overlay = None

        self.tray_icon = None
        self.floating_pill = None

        self.active_notification = None

        self.login_screen_active = True

        self.btn_send = None

        self.current_student = {
            "id": "",
            "password": "",
            "name": "",
            "role": "student",
            "session_id": None,
        }

        hide_taskbar()
        start_keyboard_hook()

        self.root.title(
            "LabGuard Terminal"
        )

        self.root.attributes(
            "-fullscreen",
            True
        )

        self.root.attributes(
            "-topmost",
            True
        )

        self.root.configure(
            bg="#0f172a"
        )

        self.root.protocol(
            "WM_DELETE_WINDOW",
            lambda: None
        )

        self.root.bind(
            "<Button-1>",
            self._on_bg_click
        )

        self.root.bind(
            "<Control-Alt-Shift-Key-X>",
            self.emergency_admin_exit
        )

        self.root.bind(
            "<Control-Alt-Shift-Key-x>",
            self.emergency_admin_exit
        )

        def reclaim_focus(
            event=None
        ):
            if self.is_session_active:
                return

            try:
                # =====================================================
                # NOTIFICATION ALWAYS HAS FIRST PRIORITY
                # =====================================================

                if (
                    self.active_notification
                    and self.active_notification.winfo_exists()
                ):
                    self.active_notification.lift()

                    self.active_notification.attributes(
                        "-topmost",
                        True
                    )

                    return

                # =====================================================
                # WI-FI MODAL
                # =====================================================

                if (
                    self.wifi_modal
                    and self.wifi_modal.winfo_exists()
                ):
                    self.wifi_modal.lift()

                    self.wifi_modal.attributes(
                        "-topmost",
                        True
                    )

                    return

                # =====================================================
                # REPORT OVERLAY
                # =====================================================

                if (
                    self.overlay
                    and self.overlay.winfo_exists()
                ):
                    self.overlay.lift()

                    self.overlay.attributes(
                        "-topmost",
                        True
                    )

                    return
                # =====================================================
                # SHUTDOWN MODAL
                # =====================================================

                if (
                    self.shutdown_modal
                    and self.shutdown_modal.winfo_exists()
                ):
                    self.shutdown_modal.lift()

                    self.shutdown_modal.attributes(
                        "-topmost",
                        True
                    )

                    return

                # =====================================================
                # LOGIN
                # =====================================================

                self.root.lift()

                self.root.attributes(
                    "-topmost",
                    True
                )

            except Exception:
                pass

        self.root.bind(
            "<FocusOut>",
            reclaim_focus
        )

        self.root.bind(
            "<Unmap>",
            reclaim_focus
        )

        # -------------------------------------------------------------
        # TOP BAR
        # -------------------------------------------------------------

        self.top_bar = tk.Frame(
            self.root,
            bg="#0f172a"
        )

        self.top_bar.pack(
            side="top",
            fill="x",
            padx=25,
            pady=20
        )

        self.station_badge = tk.Label(
            self.top_bar,
            text=(
                f"LAB: {LAB_ID}"
                f"  •  "
                f"{PC_NUMBER}"
            ),
            fg="#94a3b8",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
            padx=12,
            pady=6,
        )

        self.station_badge.pack(
            side="left"
        )

        self.net_indicator = tk.Label(
            self.top_bar,
            text="● CONNECTING...",
            fg="#eab308",
            bg="#0f172a",
            font=("Arial", 10, "bold"),
            cursor="hand2",
        )

        self.net_indicator.pack(
            side="right"
        )

        self.net_indicator.bind(
            "<Button-1>",
            lambda e:
                self.open_wifi_modal()
        )

        # -------------------------------------------------------------
        # MAIN CONTAINER
        # -------------------------------------------------------------

        self.main_container = tk.Frame(
            self.root,
            bg="#0f172a"
        )

        self.main_container.place(
            relx=0.5,
            rely=0.5,
            anchor="center"
        )

        # -------------------------------------------------------------
        # PAGE 1: LOGIN
        # -------------------------------------------------------------

        self.login_view = tk.Frame(
            self.main_container,
            bg="#0f172a"
        )

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
            text=(
                "TERMINAL ACCESS "
                "MANAGEMENT SYSTEM"
            ),
            fg="#64748b",
            bg="#0f172a",
            font=("Arial", 10, "bold"),
        ).pack(
            pady=(0, 20)
        )

        self.login_form_frame = tk.Frame(
            self.login_view,
            bg="#0f172a"
        )

        self.login_form_frame.pack(
            pady=5
        )

        tk.Label(
            self.login_form_frame,
            text="ID NUMBER",
            fg="#cbd5e1",
            bg="#0f172a",
            font=("Arial", 9, "bold"),
        ).pack(
            anchor="w",
            padx=10,
            pady=(10, 3)
        )

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

        self.entry_id.pack(
            ipady=10
        )

        self.entry_id.bind(
            "<Key>",
            self._filter_student_id_key
        )

        self.entry_id.bind(
            "<KeyRelease>",
            self._format_student_id_entry
        )

        self.entry_id.bind(
            "<Return>",
            lambda e:
                self.entry_password.focus_set()
        )

        tk.Label(
            self.login_form_frame,
            text="ACCOUNT PASSWORD",
            fg="#cbd5e1",
            bg="#0f172a",
            font=("Arial", 9, "bold"),
        ).pack(
            anchor="w",
            padx=10,
            pady=(15, 3)
        )

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

        self.entry_password.pack(
            ipady=10
        )

        self.entry_password.bind(
            "<Return>",
            lambda e:
                self.attempt_login()
        )

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

        self.btn_unlock.pack(
            pady=25
        )
        self.shutdown_button = tk.Button(
            self.root,
            text="⏻",
            command=self.shutdown_pc,
            bg="#1e293b",
            fg="#ef4444",
            activebackground="#7f1d1d",
            activeforeground="white",
            font=("Arial", 20, "bold"),
            width=3,
            height=1,
            relief="flat",
            bd=0,
            highlightthickness=1,
            highlightbackground="#334155",
            cursor="hand2"
        )

        self.shutdown_button.place(
            relx=0.0,
            rely=1.0,
            x=25,
            y=-25,
            anchor="sw"
        )


        

        # -------------------------------------------------------------
        # MAINTENANCE UI
        # -------------------------------------------------------------

        self.maintenance_frame = tk.Frame(
            self.login_view,
            bg="#0f172a"
        )

        tk.Label(
            self.maintenance_frame,
            text="🔧",
            fg="#f59e0b",
            bg="#0f172a",
            font=("Arial", 46)
        ).pack(
            pady=(15, 5)
        )

        tk.Label(
            self.maintenance_frame,
            text="TERMINAL UNDER MAINTENANCE",
            fg="#f59e0b",
            bg="#0f172a",
            font=("Arial Black", 16)
        ).pack(
            pady=5
        )

        tk.Label(
            self.maintenance_frame,
            text=(
                "This workstation is currently offline "
                "for routine servicing or repairs.\n"
                "Please transfer to another available station."
            ),
            fg="#94a3b8",
            bg="#0f172a",
            font=("Arial", 11),
            justify="center",
            wraplength=440,
        ).pack(
            pady=10
        )

        # -------------------------------------------------------------
        # PAGE 2
        # -------------------------------------------------------------

        self.checklist_view = tk.Frame(
            self.main_container,
            bg="#0f172a"
        )

        self.force_on_top()

        self.root.after(
            50,
            self.deferred_background_init
        )

    # =================================================================
    # NOTIFICATION
    # =================================================================

    def show_notification(
        self,
        title,
        message,
        color="#D4AF37"
    ):
        def create_notification():
            wifi_was_grabbed = False

            try:
                # -----------------------------------------------------
                # Temporarily release Wi-Fi modal grab.
                # -----------------------------------------------------

                if (
                    self.wifi_modal
                    and self.wifi_modal.winfo_exists()
                ):
                    try:
                        self.wifi_modal.grab_release()

                        wifi_was_grabbed = True

                    except Exception:
                        pass

                # -----------------------------------------------------
                # Destroy old notification.
                # -----------------------------------------------------

                if (
                    self.active_notification
                    and self.active_notification.winfo_exists()
                ):
                    try:
                        self.active_notification.destroy()

                    except Exception:
                        pass

                    self.active_notification = None

                # -----------------------------------------------------
                # Callback when notification disappears.
                # -----------------------------------------------------

                def restore_wifi_grab_and_focus():
                    try:
                        self.active_notification = None

                    except Exception:
                        pass

                    if not wifi_was_grabbed:
                        return

                    try:
                        if (
                            not self.wifi_modal
                            or not self.wifi_modal.winfo_exists()
                        ):
                            return

                        # Restore modal grab.
                        self.wifi_modal.grab_set()

                        self.wifi_modal.lift()

                        self.wifi_modal.attributes(
                            "-topmost",
                            True
                        )

                    except Exception as e:
                        print(
                            f"[WIFI GRAB RESTORE ERROR] {e}"
                        )

                    # -------------------------------------------------
                    # Give the Wi-Fi password field keyboard focus.
                    # -------------------------------------------------

                    def restore_password_focus():
                        try:
                            if (
                                not self.wifi_modal
                                or not self.wifi_modal.winfo_exists()
                            ):
                                return

                            self.wifi_modal.lift()

                            self.wifi_modal.attributes(
                                "-topmost",
                                True
                            )

                            if (
                                hasattr(
                                    self,
                                    "wifi_pass"
                                )
                                and self.wifi_pass.winfo_exists()
                            ):
                                self.wifi_pass.focus_force()

                                self.wifi_pass.icursor(
                                    tk.END
                                )

                        except Exception as e:
                            print(
                                f"[WIFI FOCUS RESTORE ERROR] {e}"
                            )

                    try:
                        self.root.after(
                            100,
                            restore_password_focus
                        )

                    except Exception:
                        pass

                # -----------------------------------------------------
                # Create independent notification window.
                # -----------------------------------------------------

                self.active_notification = (
                    CinematicNotify(
                        self.root,
                        title,
                        message,
                        color,
                        position="center",
                        on_close=restore_wifi_grab_and_focus
                    )
                )

                # -----------------------------------------------------
                # Notification stays above Wi-Fi modal.
                # -----------------------------------------------------

                self.active_notification.lift()

                self.active_notification.attributes(
                    "-topmost",
                    True
                )

            except Exception as e:
                print(
                    f"[NOTIFICATION ERROR] {e}"
                )

                if wifi_was_grabbed:
                    try:
                        if (
                            self.wifi_modal
                            and self.wifi_modal.winfo_exists()
                        ):
                            self.wifi_modal.grab_set()

                            self.wifi_modal.lift()

                            if (
                                hasattr(
                                    self,
                                    "wifi_pass"
                                )
                                and self.wifi_pass.winfo_exists()
                            ):
                                self.wifi_pass.focus_force()

                    except Exception:
                        pass

        try:
            self.root.after(
                0,
                create_notification
            )

        except Exception:
            pass

    # =================================================================
    # BACKGROUND CLICK
    # =================================================================

    def _on_bg_click(
        self,
        event
    ):
        if (
            not self.is_session_active
            and not (
                self.wifi_modal
                and self.wifi_modal.winfo_exists()
            )
            and not (
                self.overlay
                and self.overlay.winfo_exists()
            )
            and not (
                self.active_notification
                and self.active_notification.winfo_exists()
            )
        ):
            if event.widget not in (
                self.entry_id,
                self.entry_password,
                self.btn_unlock,
                self.shutdown_button
            ):
                if self.login_view.winfo_ismapped():
                    self.entry_id.focus_set()

    # =================================================================
    # BACKGROUND INITIALIZATION
    # =================================================================

    def deferred_background_init(self):
        threading.Thread(
            target=register_shutdown_hooks,
            daemon=True
        ).start()

        threading.Thread(
            target=self.network_monitor_loop,
            daemon=True
        ).start()

    # =================================================================
    # STUDENT ID FORMATTING
    # =================================================================

    def _filter_student_id_key(
        self,
        event
    ):
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
            "End"
        }:
            return

        if (
            event.char
            and not event.char.isdigit()
        ):
            return "break"

    def _format_student_id_entry(
        self,
        event=None
    ):
        if event and event.keysym in {
            "BackSpace",
            "Delete",
            "Left",
            "Right",
            "Home",
            "End"
        }:
            return

        target = (
            event.widget
            if event
            else self.entry_id
        )

        current_value = target.get()

        cursor_pos = target.index(
            tk.INSERT
        )

        was_at_end = (
            cursor_pos
            == len(current_value)
        )

        digits = re.sub(
            r"\D",
            "",
            current_value
        )[:12]

        formatted = (
            self._format_student_id_text(
                digits
            )
        )

        if formatted != current_value:
            target.delete(
                0,
                tk.END
            )

            target.insert(
                0,
                formatted
            )

            if was_at_end:
                target.icursor(
                    tk.END
                )

            else:
                old_prefix_digits = len(
                    re.sub(
                        r"\D",
                        "",
                        current_value[
                            :cursor_pos
                        ]
                    )
                )

                new_pos = 0
                digit_count = 0

                for char in formatted:
                    if (
                        digit_count
                        == old_prefix_digits
                    ):
                        break

                    if char.isdigit():
                        digit_count += 1

                    new_pos += 1

                target.icursor(
                    new_pos
                )

    def _format_student_id_text(
        self,
        value
    ):
        if len(value) <= 2:
            return value

        if len(value) <= 6:
            return (
                f"{value[:2]}-"
                f"{value[2:]}"
            )

        return (
            f"{value[:2]}-"
            f"{value[2:6]}-"
            f"{value[6:]}"
        )

    # =================================================================
    # MAINTENANCE
    # =================================================================

    def show_maintenance_ui(self):
        if not self.is_maintenance_mode:
            self.is_maintenance_mode = True

            self.login_form_frame.pack_forget()

            self.maintenance_frame.pack(
                pady=10
            )

    def restore_login_ui(self):
        if self.is_maintenance_mode:
            self.is_maintenance_mode = False

            self.maintenance_frame.pack_forget()

            self.login_form_frame.pack(
                pady=10
            )

            self.root.after(
                100,
                self.restore_login_focus
            )

    # =================================================================
    # LOGIN FOCUS RESTORE
    # =================================================================

    def restore_login_focus(self):
        try:
            if self.wifi_active:
                return

            if not self.root.winfo_exists():
                return

            if not self.login_view.winfo_ismapped():
                return

            self.root.deiconify()

            self.root.lift()

            self.root.attributes(
                "-topmost",
                True
            )

            self.root.update_idletasks()

            self.root.focus_force()

            self.entry_id.focus_force()

            self.entry_id.icursor(
                tk.END
            )

        except tk.TclError:
            pass

        except Exception as e:
            print(
                f"[DEBUG] Focus restore error: {e}"
            )

    # =================================================================
    # HARDWARE CHECKLIST
    # =================================================================

    def show_checklist_screen(
        self,
        student_name
    ):
        self.login_screen_active = False

        self.login_view.pack_forget()

        for widget in (
            self.checklist_view.winfo_children()
        ):
            widget.destroy()

        self.checklist_view.pack(
            fill="both",
            expand=True
        )

        badge_frame = tk.Frame(
            self.checklist_view,
            bg="#0f172a"
        )

        badge_frame.pack(
            pady=(0, 6)
        )

        tk.Label(
            badge_frame,
            text=(
                "STEP 2 OF 2 • "
                "MANDATORY WORKSTATION AUDIT"
            ),
            fg="#D4AF37",
            bg="#1e293b",
            font=("Arial", 9, "bold"),
            padx=14,
            pady=4,
        ).pack()

        tk.Label(
            self.checklist_view,
            text=(
                f"Welcome, "
                f"{student_name.upper()}!"
            ),
            fg="white",
            bg="#0f172a",
            font=("Arial Black", 22),
        ).pack(
            pady=(6, 2)
        )

        tk.Label(
            self.checklist_view,
            text=(
                "Inspect all workstation peripherals. "
                "Check items that are operational, "
                "leave defective items unchecked.\n"
                "Unchecked components will be recorded "
                "as non-operational in your session report."
            ),
            fg="#94a3b8",
            bg="#0f172a",
            font=("Arial", 10),
            justify="center",
            wraplength=650,
        ).pack(
            pady=(0, 16)
        )

        self.hardware_items = [
            {
                "id": "system_unit",
                "icon": "🖥️",
                "title": "System Unit",
                "desc": (
                    "Power button working, casing sealed, "
                    "no abnormal fan noise."
                )
            },
            {
                "id": "monitor",
                "icon": "🖥️",
                "title": "Display Monitor",
                "desc": (
                    "Screen clear, no cracks, lines, "
                    "or video signal loss."
                )
            },
            {
                "id": "avr",
                "icon": "⚡",
                "title": "Power Unit (AVR)",
                "desc": (
                    "Voltage regulator active, "
                    "power indicator light on, grounded."
                )
            },
            {
                "id": "mouse",
                "icon": "🖱️",
                "title": "Optical Mouse",
                "desc": (
                    "Laser tracking smooth, left & "
                    "right click responsive."
                )
            },
            {
                "id": "keyboard",
                "icon": "⌨️",
                "title": "Keyboard Unit",
                "desc": (
                    "All keycaps present, typing responsive, "
                    "no sticky keys."
                )
            },
            {
                "id": "cables",
                "icon": "🔌",
                "title": "Power & I/O Cables",
                "desc": (
                    "Display, power, and peripheral cords "
                    "securely plugged in."
                )
            },
        ]

        self.check_states = {}
        self.checklist_widgets = {}

        cards_container = tk.Frame(
            self.checklist_view,
            bg="#0f172a"
        )

        cards_container.pack(
            pady=4
        )

        for idx, item in enumerate(
            self.hardware_items
        ):
            var = tk.BooleanVar(
                value=False
            )

            self.check_states[
                item["id"]
            ] = var

            r = idx // 2
            c = idx % 2

            card = tk.Frame(
                cards_container,
                bg="#1e293b",
                width=310,
                height=65,
                highlightthickness=1,
                highlightbackground="#334155"
            )

            card.grid(
                row=r,
                column=c,
                padx=8,
                pady=6,
                sticky="nsew"
            )

            card.pack_propagate(
                False
            )

            lbl_check = tk.Label(
                card,
                text="[   ]",
                fg="#64748b",
                bg="#1e293b",
                font=("Courier", 12, "bold"),
                cursor="hand2"
            )

            lbl_check.pack(
                side="left",
                padx=(12, 6)
            )

            content = tk.Frame(
                card,
                bg="#1e293b",
                cursor="hand2"
            )

            content.pack(
                side="left",
                fill="both",
                expand=True,
                pady=6
            )

            title_lbl = tk.Label(
                content,
                text=(
                    f"{item['icon']} "
                    f"{item['title']}"
                ),
                fg="#f8fafc",
                bg="#1e293b",
                font=("Arial", 10, "bold"),
                anchor="w",
            )

            title_lbl.pack(
                anchor="w"
            )

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

            desc_lbl.pack(
                anchor="w"
            )

            self.checklist_widgets[
                item["id"]
            ] = {
                "var": var,
                "lbl": lbl_check,
                "card": card
            }

            def toggle_item(
                item_id=item["id"]
            ):
                current_val = (
                    self.checklist_widgets[
                        item_id
                    ]["var"].get()
                )

                self._set_checklist_item_state(
                    item_id,
                    not current_val
                )

                self._update_checklist_button_state()

            for widget in (
                card,
                lbl_check,
                content,
                title_lbl,
                desc_lbl
            ):
                widget.bind(
                    "<Button-1>",
                    lambda e,
                    func=toggle_item:
                        func()
                )

        quick_select_frame = tk.Frame(
            self.checklist_view,
            bg="#0f172a"
        )

        quick_select_frame.pack(
            pady=(8, 12)
        )

        btn_select_all = tk.Button(
            quick_select_frame,
            text="✔ SELECT ALL AS OPERATIONAL",
            command=(
                self.select_all_checklist_items
            ),
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

        action_frame = tk.Frame(
            self.checklist_view,
            bg="#0f172a"
        )

        action_frame.pack(
            pady=5
        )

        self.btn_proceed = tk.Button(
            action_frame,
            text=(
                "CONFIRM & PROCEED "
                "TO DESKTOP"
            ),
            command=(
                self.complete_checklist_and_unlock
            ),
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

        self.btn_proceed.pack(
            side="left",
            padx=8
        )

        btn_cancel = tk.Button(
            action_frame,
            text="CANCEL & LOGOUT",
            command=(
                self.return_to_login_screen
            ),
            bg="#1e293b",
            fg="#94a3b8",
            font=("Arial", 9, "bold"),
            width=18,
            height=2,
            relief="flat",
            cursor="hand2",
        )

        btn_cancel.pack(
            side="left",
            padx=8
        )

        report_pill = tk.Frame(
            self.checklist_view,
            bg="#1e293b",
            cursor="hand2"
        )

        report_pill.pack(
            pady=(18, 0)
        )

        lbl_warn_icon = tk.Label(
            report_pill,
            text="⚠",
            fg="#ef4444",
            bg="#1e293b",
            font=("Arial", 11, "bold"),
            cursor="hand2"
        )

        lbl_warn_icon.pack(
            side="left",
            padx=(14, 4),
            pady=6
        )

        lbl_warn_text = tk.Label(
            report_pill,
            text=(
                "SOMETHING BROKEN OR MISSING? "
                "CLICK HERE TO LOG A FORMAL TICKET"
            ),
            fg="#ef4444",
            bg="#1e293b",
            font=("Arial", 8, "bold"),
            cursor="hand2",
        )

        lbl_warn_text.pack(
            side="left",
            padx=(0, 14),
            pady=6
        )

        for widget in (
            report_pill,
            lbl_warn_icon,
            lbl_warn_text
        ):
            widget.bind(
                "<Button-1>",
                lambda e:
                    self.open_report_overlay(
                        prefill=True
                    )
            )

    def _set_checklist_item_state(
        self,
        item_id,
        is_checked
    ):
        if item_id in self.checklist_widgets:
            widget_data = (
                self.checklist_widgets[
                    item_id
                ]
            )

            widget_data["var"].set(
                is_checked
            )

            if is_checked:
                widget_data["lbl"].config(
                    text="[ ✔ ]",
                    fg="#10b981"
                )

                widget_data["card"].config(
                    highlightbackground="#10b981"
                )

            else:
                widget_data["lbl"].config(
                    text="[ ✕ ]",
                    fg="#ef4444"
                )

                widget_data["card"].config(
                    highlightbackground="#ef4444"
                )

    def select_all_checklist_items(self):
        for item_id in (
            self.checklist_widgets.keys()
        ):
            self._set_checklist_item_state(
                item_id,
                True
            )

        self._update_checklist_button_state()

    def _update_checklist_button_state(self):
        checked_count = sum(
            1
            for v in self.check_states.values()
            if v.get()
        )

        if (
            checked_count
            == len(self.check_states)
        ):
            self.btn_proceed.config(
                text=(
                    "CONFIRM & PROCEED "
                    "TO DESKTOP"
                ),
                bg="#10b981",
                fg="white",
            )

        else:
            self.btn_proceed.config(
                text=(
                    f"PROCEED "
                    f"({checked_count}/"
                    f"{len(self.check_states)} "
                    f"OPERATIONAL)"
                ),
                bg="#f59e0b",
                fg="#0f172a",
            )

    def return_to_login_screen(self):
        self.current_student = {
            "id": "",
            "password": "",
            "name": "",
            "role": "student",
            "session_id": None
        }

        self.login_screen_active = True

        self.checklist_view.pack_forget()

        self.login_view.pack()

        self.entry_password.delete(
            0,
            tk.END
        )

        self.btn_unlock.config(
            state="normal",
            text="AUTHENTICATE & INSPECT"
        )

        self.root.after(
            100,
            self.restore_login_focus
        )

    # =================================================================
    # CHECKLIST SUBMISSION
    # =================================================================

    def complete_checklist_and_unlock(self):
        self.btn_proceed.config(
            state="disabled",
            text="SAVING INSPECTION AUDIT..."
        )

        checklist_payload = {
            item_id: var.get()
            for item_id, var
            in self.check_states.items()
        }

        request_body = {
            "pc_number": PC_NUMBER,
            "lab": LAB_ID,
            "student_id": (
                self.current_student["id"]
            ),
            "session_id": (
                self.current_student[
                    "session_id"
                ]
            ),
            "checklist": checklist_payload,
        }

        def send_checklist_to_laravel():
            try:
                session = (
                    get_authenticated_session()
                )

                resp = session.post(
                    f"{API_URL}/checklist",
                    json=request_body,
                    timeout=6
                )

                if resp.status_code in (
                    200,
                    201
                ):
                    print(
                        "[AUDIT] Workstation checklist "
                        "saved to Laravel successfully."
                    )

                else:
                    print(
                        "[AUDIT WARN] Backend checklist "
                        f"response: {resp.status_code} - "
                        f"{resp.text}"
                    )

            except Exception as e:
                print(
                    "[AUDIT ERROR] Failed to send "
                    f"checklist to Laravel: {e}"
                )

            try:
                self.root.after(
                    0,
                    self._finalize_unlock_session
                )

            except Exception:
                pass

        threading.Thread(
            target=send_checklist_to_laravel,
            daemon=True
        ).start()

    def _finalize_unlock_session(self):
        self.login_screen_active = False

        self.show_notification(
            "Session Started",
            (
                "Terminal unlocked. "
                f"Welcome, "
                f"{self.current_student['name']}!"
            ),
            color="#10b981"
        )

        self.root.after(
            1000,
            self.hide_terminal
        )

    # =================================================================
    # NETWORK MONITOR
    # =================================================================

    def network_monitor_loop(self):
        while True:
            try:
                lab_param = urllib.parse.quote(
                    str(LAB_ID)
                )

                pc_param = urllib.parse.quote(
                    str(PC_NUMBER)
                )

                url = (
                    f"{API_URL}/status/"
                    f"{lab_param}/"
                    f"{pc_param}"
                )

                session = (
                    get_authenticated_session()
                )

                res = session.get(
                    url,
                    timeout=3
                )

                if res.status_code == 200:
                    try:
                        data = res.json()

                    except Exception:
                        data = {}

                    pc_status = (
                        data.get("status")
                        or data.get(
                            "data",
                            {}
                        ).get("status")
                    )

                    try:
                        self.root.after(
                            0,
                            self.update_net_status,
                            True
                        )

                        if (
                            pc_status
                            and str(
                                pc_status
                            ).lower()
                            == "maintenance"
                        ):
                            self.root.after(
                                0,
                                self.show_maintenance_ui
                            )

                        else:
                            self.root.after(
                                0,
                                self.restore_login_ui
                            )

                    except Exception:
                        pass

                elif res.status_code == 404:
                    try:
                        self.root.after(
                            0,
                            self.update_net_status,
                            True
                        )

                        self.root.after(
                            0,
                            self.restore_login_ui
                        )

                    except Exception:
                        pass

                else:
                    try:
                        self.root.after(
                            0,
                            self.update_net_status,
                            False
                        )

                    except Exception:
                        pass

            except Exception:
                try:
                    self.root.after(
                        0,
                        self.update_net_status,
                        False
                    )

                except Exception:
                    pass

            time.sleep(5)

    def update_net_status(
        self,
        is_online
    ):
        try:
            if is_online:
                self.net_indicator.config(
                    text="● ONLINE",
                    fg="#10b981"
                )

            else:
                self.net_indicator.config(
                    text=(
                        "▲ OFFLINE "
                        "(CLICK TO FIX WI-FI)"
                    ),
                    fg="#ef4444"
                )

        except tk.TclError:
            pass

    # =================================================================
    # WI-FI MODAL
    # =================================================================

    def open_wifi_modal(self):
        if (
            self.wifi_modal
            and self.wifi_modal.winfo_exists()
        ):
            try:
                self.wifi_modal.lift()

                self.wifi_modal.attributes(
                    "-topmost",
                    True
                )

                self.wifi_modal.focus_force()

            except Exception:
                pass

            return

        self.wifi_active = True
        self.login_screen_active = False

        self.root.attributes(
            "-topmost",
            False
        )

        self.wifi_modal = tk.Toplevel(
            self.root
        )

        self.wifi_modal.configure(
            bg="#1e293b"
        )

        self.wifi_modal.overrideredirect(
            True
        )

        width = 480
        height = 550

        screen_w = (
            self.root.winfo_screenwidth()
        )

        screen_h = (
            self.root.winfo_screenheight()
        )

        x = (
            screen_w
            // 2
        ) - (
            width
            // 2
        )

        y = (
            screen_h
            // 2
        ) - (
            height
            // 2
        )

        self.wifi_modal.geometry(
            f"{width}x{height}+{x}+{y}"
        )

        self.wifi_modal.attributes(
            "-topmost",
            True
        )

        self.wifi_modal.transient(
            self.root
        )

        def close_wifi(
            event=None
        ):
            self.wifi_active = False

            modal = self.wifi_modal

            self.wifi_modal = None

            if modal is not None:
                try:
                    modal.grab_release()

                except Exception:
                    pass

                try:
                    modal.focus_release()

                except Exception:
                    pass

                try:
                    modal.attributes(
                        "-topmost",
                        False
                    )

                except Exception:
                    pass

                try:
                    modal.destroy()

                except Exception:
                    pass

            try:
                self.root.deiconify()

                self.root.attributes(
                    "-topmost",
                    True
                )

                self.root.lift()

                self.root.update_idletasks()

            except Exception:
                pass

            self.login_screen_active = (
                self.login_view.winfo_ismapped()
            )

            try:
                self.root.after(
                    100,
                    self.restore_login_focus
                )

            except Exception:
                pass

        self.wifi_modal.protocol(
            "WM_DELETE_WINDOW",
            close_wifi
        )

        self.wifi_modal.bind(
            "<Escape>",
            close_wifi
        )

        header_frame = tk.Frame(
            self.wifi_modal,
            bg="#1e293b"
        )

        header_frame.pack(
            fill="x",
            padx=15,
            pady=(15, 0)
        )

        tk.Label(
            header_frame,
            text="NETWORK SETTINGS",
            fg="#D4AF37",
            bg="#1e293b",
            font=("Arial Black", 14)
        ).pack(
            side="left"
        )

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

        btn_x.pack(
            side="right"
        )

        tk.Label(
            self.wifi_modal,
            text=(
                "Select an available Wi-Fi access point "
                "to connect."
            ),
            fg="#94a3b8",
            bg="#1e293b",
            font=("Arial", 9),
        ).pack(
            anchor="w",
            padx=15,
            pady=(2, 10)
        )

        list_frame = tk.Frame(
            self.wifi_modal,
            bg="#0f172a"
        )

        list_frame.pack(
            fill="both",
            expand=True,
            padx=20,
            pady=5
        )

        self.wifi_listbox = tk.Listbox(
            list_frame,
            bg="#0f172a",
            fg="white",
            font=("Arial", 11),
            selectbackground="#D4AF37",
            selectforeground="#0f172a",
            borderwidth=0,
            highlightthickness=0,
        )

        self.wifi_listbox.pack(
            side="left",
            fill="both",
            expand=True,
            padx=5,
            pady=5
        )

        tk.Label(
            self.wifi_modal,
            text="Security Key / Password",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold")
        ).pack(
            anchor="w",
            padx=20,
            pady=(10, 2)
        )

        self.wifi_pass = tk.Entry(
            self.wifi_modal,
            font=("Arial", 12),
            show="*",
            bg="#0f172a",
            fg="white",
            border=0,
            insertbackground="white"
        )

        self.wifi_pass.pack(
            fill="x",
            padx=20,
            pady=5,
            ipady=6
        )

        btn_frame = tk.Frame(
            self.wifi_modal,
            bg="#1e293b"
        )

        btn_frame.pack(
            pady=20
        )

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
        ).pack(
            side="left",
            padx=5
        )

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
        ).pack(
            side="left",
            padx=5
        )

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
        ).pack(
            side="left",
            padx=5
        )

        try:
            self.wifi_modal.grab_set()

        except Exception:
            pass

        self.wifi_modal.lift()

        self.wifi_modal.focus_force()

        self.wifi_pass.focus_force()

        self.scan_wifi_networks()

    def _set_wifi_list_items(
        self,
        items
    ):
        try:
            if (
                self.wifi_modal
                and self.wifi_modal.winfo_exists()
            ):
                self.wifi_listbox.delete(
                    0,
                    tk.END
                )

                for item in items:
                    self.wifi_listbox.insert(
                        tk.END,
                        item
                    )

        except Exception as e:
            print(
                f"[DEBUG] Wi-Fi list update error: {e}"
            )

    def scan_wifi_networks(self):
        try:
            if (
                self.wifi_modal
                and self.wifi_modal.winfo_exists()
            ):
                self.wifi_listbox.delete(
                    0,
                    tk.END
                )

                self.wifi_listbox.insert(
                    tk.END,
                    (
                        "Turning on Wi-Fi adapter "
                        "& scanning..."
                    )
                )

        except Exception:
            return

        def execute_scan():
            try:
                enable_wifi_adapter()

                time.sleep(2.5)

                found_ssids = (
                    get_native_wifi_networks()
                )

                if not found_ssids:
                    try:
                        output = (
                            subprocess.check_output(
                                "netsh wlan show networks",
                                shell=True,
                                stderr=subprocess.STDOUT,
                                creationflags=(
                                    subprocess.CREATE_NO_WINDOW
                                    if hasattr(
                                        subprocess,
                                        "CREATE_NO_WINDOW"
                                    )
                                    else 0
                                )
                            )
                            .decode(
                                "utf-8",
                                errors="ignore"
                            )
                        )

                        ssids = re.findall(
                            r"SSID\s+\d+\s*:\s*(.+)",
                            output
                        )

                        found_ssids = sorted(
                            list(
                                set(
                                    [
                                        s.strip()
                                        for s in ssids
                                        if (
                                            s.strip()
                                            and not s.strip()
                                            .startswith(
                                                "SSID"
                                            )
                                        )
                                    ]
                                )
                            )
                        )

                    except Exception:
                        pass

                if found_ssids:
                    self.root.after(
                        0,
                        lambda:
                            self._set_wifi_list_items(
                                found_ssids
                            )
                    )

                else:
                    self.root.after(
                        0,
                        lambda:
                            self._set_wifi_list_items(
                                [
                                    "No networks found. "
                                    "Try scanning again."
                                ]
                            )
                    )

            except Exception as e:
                print(
                    f"[WIFI SCAN ERROR] {e}"
                )

                try:
                    self.root.after(
                        0,
                        lambda:
                            self._set_wifi_list_items(
                                [
                                    "Wi-Fi scan failed. "
                                    "Try again."
                                ]
                            )
                    )

                except Exception:
                    pass

        threading.Thread(
            target=execute_scan,
            daemon=True
        ).start()

    def connect_to_wifi(self):
        try:
            selection = (
                self.wifi_listbox.curselection()
            )

            if not selection:
                raise ValueError(
                    "No network selected"
                )

            selected_ssid = (
                self.wifi_listbox.get(
                    selection[0]
                )
            )

        except Exception:
            self.show_notification(
                "Selection Required",
                "Please click an SSID from the list.",
                color="#ef4444"
            )

            return

        if (
            not selected_ssid
            or selected_ssid.startswith(
                "No networks found"
            )
            or selected_ssid.startswith(
                "Turning on Wi-Fi"
            )
            or selected_ssid.startswith(
                "Wi-Fi scan failed"
            )
        ):
            self.show_notification(
                "Invalid Network",
                "Please select a valid Wi-Fi network.",
                color="#ef4444"
            )

            return

        password = (
            self.wifi_pass.get().strip()
        )

        def execute_connection():
            try:
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
    <SSIDConfig>
        <SSID>
            <name>{selected_ssid}</name>
        </SSID>
    </SSIDConfig>
    <connectionType>ESS</connectionType>
    <connectionMode>auto</connectionMode>
    <MSM>{security_block}</MSM>
</WLANProfile>"""

                temp_dir = tempfile.gettempdir()

                safe_hash = abs(
                    hash(selected_ssid)
                )

                filename = os.path.join(
                    temp_dir,
                    f"wifi_{safe_hash}.xml"
                )

                with open(
                    filename,
                    "w",
                    encoding="utf-8"
                ) as f:
                    f.write(profile_xml)

                subprocess.run(
                    f'netsh wlan add profile '
                    f'filename="{filename}"',
                    shell=True,
                    capture_output=True,
                    text=True,
                    creationflags=(
                        subprocess.CREATE_NO_WINDOW
                        if hasattr(
                            subprocess,
                            "CREATE_NO_WINDOW"
                        )
                        else 0
                    )
                )

                res_conn = subprocess.run(
                    f'netsh wlan connect '
                    f'name="{selected_ssid}"',
                    shell=True,
                    capture_output=True,
                    text=True,
                    creationflags=(
                        subprocess.CREATE_NO_WINDOW
                        if hasattr(
                            subprocess,
                            "CREATE_NO_WINDOW"
                        )
                        else 0
                    )
                )

                if os.path.exists(filename):
                    try:
                        os.remove(filename)
                    except Exception:
                        pass

                if res_conn.returncode == 0:
                    self.show_notification(
                        "Connecting",
                        (
                            f"Connecting to "
                            f"{selected_ssid}..."
                        ),
                        color="#3b82f6"
                    )

                else:
                    self.show_notification(
                        "Wi-Fi Error",
                        (
                            "Could not connect to "
                            "the selected network."
                        ),
                        color="#ef4444"
                    )

            except Exception as e:
                print(
                    f"[WIFI CONNECTION ERROR] {e}"
                )

                self.show_notification(
                    "Wi-Fi Error",
                    (
                        "The Wi-Fi connection "
                        "could not be completed."
                    ),
                    color="#ef4444"
                )

        threading.Thread(
            target=execute_connection,
            daemon=True
        ).start()

    # =================================================================
    # REPORT OVERLAY
    # =================================================================

    def open_report_overlay(
        self,
        prefill=False
    ):
        if (
            self.overlay
            and self.overlay.winfo_exists()
        ):
            try:
                self.overlay.lift()

                self.overlay.attributes(
                    "-topmost",
                    True
                )

            except Exception:
                pass

            return

        self.root.attributes(
            "-topmost",
            False
        )

        self.overlay = tk.Toplevel(
            self.root
        )

        self.overlay.configure(
            bg="#1e293b"
        )

        self.overlay.overrideredirect(
            True
        )

        width = 480

        height = (
            540
            if prefill
            else 660
        )

        screen_w = (
            self.root.winfo_screenwidth()
        )

        screen_h = (
            self.root.winfo_screenheight()
        )

        x = (
            screen_w
            // 2
        ) - (
            width
            // 2
        )

        y = (
            screen_h
            // 2
        ) - (
            height
            // 2
        )

        self.overlay.geometry(
            f"{width}x{height}+{x}+{y}"
        )

        self.overlay.attributes(
            "-topmost",
            True
        )

        self.overlay.transient(
            self.root
        )

        tk.Label(
            self.overlay,
            text="REPORT A PROBLEM",
            fg="#ef4444",
            bg="#1e293b",
            font=("Arial Black", 16)
        ).pack(
            pady=(22, 2)
        )

        tk.Label(
            self.overlay,
            text=(
                f"Logging an issue for "
                f"terminal {PC_NUMBER}"
            ),
            fg="#94a3b8",
            bg="#1e293b",
            font=("Arial", 9),
        ).pack(
            pady=(0, 8)
        )

        self.report_student_id = tk.Entry(
            self.overlay
        )

        self.report_password = tk.Entry(
            self.overlay
        )

        if (
            prefill
            and self.current_student["id"]
        ):
            self.report_student_id.insert(
                0,
                self.current_student["id"]
            )

            self.report_password.insert(
                0,
                self.current_student["password"]
            )

            identity_pill = tk.Frame(
                self.overlay,
                bg="#0f172a",
                highlightthickness=1,
                highlightbackground="#334155"
            )

            identity_pill.pack(
                fill="x",
                padx=50,
                pady=(6, 12)
            )

            tk.Label(
                identity_pill,
                text=(
                    f"🛡️ Verified Identity: "
                    f"{self.current_student['name']} "
                    f"({self.current_student['id']})"
                ),
                fg="#10b981",
                bg="#0f172a",
                font=("Arial", 9, "bold"),
                padx=10,
                pady=8,
            ).pack()

        else:
            tk.Label(
                self.overlay,
                text="Student Number / ID",
                fg="white",
                bg="#1e293b",
                font=("Arial", 9, "bold")
            ).pack(
                anchor="w",
                padx=50,
                pady=(10, 2)
            )

            self.report_student_id = tk.Entry(
                self.overlay,
                font=("Arial", 11),
                bg="#0f172a",
                fg="white",
                border=0,
                insertbackground="white"
            )

            self.report_student_id.pack(
                fill="x",
                padx=50,
                ipady=6
            )

            self.report_student_id.bind(
                "<Key>",
                self._filter_student_id_key
            )

            self.report_student_id.bind(
                "<KeyRelease>",
                self._format_student_id_entry
            )

            tk.Label(
                self.overlay,
                text="Account Password",
                fg="white",
                bg="#1e293b",
                font=("Arial", 9, "bold")
            ).pack(
                anchor="w",
                padx=50,
                pady=(10, 2)
            )

            self.report_password = tk.Entry(
                self.overlay,
                font=("Arial", 11),
                show="*",
                bg="#0f172a",
                fg="white",
                border=0,
                insertbackground="white"
            )

            self.report_password.pack(
                fill="x",
                padx=50,
                ipady=6
            )

        # -------------------------------------------------------------
        # CATEGORY
        # -------------------------------------------------------------

        tk.Label(
            self.overlay,
            text="Problem Category",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold")
        ).pack(
            anchor="w",
            padx=50,
            pady=(6, 2)
        )

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

        self.issue_var = tk.StringVar(
            value=categories[0]
        )

        dropdown_btn = tk.Frame(
            self.overlay,
            bg="#0f172a",
            cursor="hand2",
            highlightthickness=1,
            highlightbackground="#334155"
        )

        dropdown_btn.pack(
            fill="x",
            padx=50,
            ipady=6
        )

        lbl_selected = tk.Label(
            dropdown_btn,
            textvariable=self.issue_var,
            fg="white",
            bg="#0f172a",
            font=("Arial", 10),
            cursor="hand2"
        )

        lbl_selected.pack(
            side="left",
            padx=10
        )

        lbl_arrow = tk.Label(
            dropdown_btn,
            text="▼",
            fg="#D4AF37",
            bg="#0f172a",
            font=("Arial", 9),
            cursor="hand2"
        )

        lbl_arrow.pack(
            side="right",
            padx=10
        )

        options_frame = tk.Frame(
            self.overlay,
            bg="#1e293b",
            highlightthickness=1,
            highlightbackground="#D4AF37"
        )

        for cat in categories:
            row = tk.Label(
                options_frame,
                text=cat,
                fg="white",
                bg="#1e293b",
                font=("Arial", 9),
                anchor="w",
                padx=10,
                pady=4,
                cursor="hand2"
            )

            row.pack(
                fill="x"
            )

            def make_hover(
                r=row
            ):
                r.bind(
                    "<Enter>",
                    lambda e:
                        r.config(
                            bg="#334155",
                            fg="#D4AF37"
                        )
                )

                r.bind(
                    "<Leave>",
                    lambda e:
                        r.config(
                            bg="#1e293b",
                            fg="white"
                        )
                )

            make_hover()

            def pick_option(
                c=cat
            ):
                self.issue_var.set(
                    c
                )

                options_frame.place_forget()

                lbl_arrow.config(
                    text="▼"
                )

            row.bind(
                "<Button-1>",
                lambda e,
                func=pick_option:
                    func()
            )

        def toggle_dropdown(
            event=None
        ):
            if options_frame.winfo_ismapped():
                options_frame.place_forget()

                lbl_arrow.config(
                    text="▼"
                )

            else:
                dropdown_btn.update_idletasks()

                bx = (
                    dropdown_btn.winfo_x()
                )

                by = (
                    dropdown_btn.winfo_y()
                    + dropdown_btn.winfo_height()
                )

                bw = (
                    dropdown_btn.winfo_width()
                )

                options_frame.place(
                    x=bx,
                    y=by,
                    width=bw
                )

                options_frame.lift()

                lbl_arrow.config(
                    text="▲"
                )

        dropdown_btn.bind(
            "<Button-1>",
            toggle_dropdown
        )

        lbl_selected.bind(
            "<Button-1>",
            toggle_dropdown
        )

        lbl_arrow.bind(
            "<Button-1>",
            toggle_dropdown
        )

        # -------------------------------------------------------------
        # REMARKS
        # -------------------------------------------------------------

        tk.Label(
            self.overlay,
            text="Describe the Problem",
            fg="white",
            bg="#1e293b",
            font=("Arial", 9, "bold")
        ).pack(
            anchor="w",
            padx=50,
            pady=(10, 2)
        )

        self.remarks_box = tk.Text(
            self.overlay,
            height=4,
            font=("Arial", 10),
            bg="#0f172a",
            fg="white",
            border=0,
            padx=12,
            pady=8,
            insertbackground="white"
        )

        self.remarks_box.pack(
            padx=50,
            fill="x"
        )

        # -------------------------------------------------------------
        # CLOSE OVERLAY
        # -------------------------------------------------------------

        def close_overlay():
            overlay_window = self.overlay

            self.overlay = None

            if overlay_window:
                try:
                    overlay_window.destroy()

                except tk.TclError:
                    pass

                except Exception:
                    pass

            try:
                self.root.attributes(
                    "-topmost",
                    True
                )

                self.root.lift()

            except tk.TclError:
                pass

        # -------------------------------------------------------------
        # REPORT SUBMISSION
        # -------------------------------------------------------------

        def handle_submit():
            if (
                not self.overlay
                or not self.overlay.winfo_exists()
            ):
                return

            student_id = (
                self.current_student["id"]
                if prefill
                else self.report_student_id.get().strip()
            )

            password = (
                self.current_student["password"]
                if prefill
                else self.report_password.get()
            )

            remarks = (
                self.remarks_box.get(
                    "1.0",
                    tk.END
                ).strip()
            )

            if not student_id or not password:
                self.show_notification(
                    "Identity Required",
                    (
                        "Your student credentials are "
                        "required to verify the report."
                    ),
                    color="#ef4444"
                )

                return

            if not remarks:
                self.show_notification(
                    "Incomplete Report",
                    (
                        "Please describe the hardware "
                        "or station issue before submitting."
                    ),
                    color="#ef4444"
                )

                return

            self.btn_send.config(
                state="disabled",
                text="DISPATCHING..."
            )

            payload = {
                "pc_number": PC_NUMBER,
                "student_id": student_id,
                "password": password,
                "issue_type": self.issue_var.get(),
                "remarks": remarks,
            }

            def async_report():
                try:
                    session = (
                        get_authenticated_session()
                    )

                    response = session.post(
                        f"{API_URL}/alerts",
                        json=payload,
                        timeout=8
                    )

                    if response.status_code in (
                        200,
                        201
                    ):
                        def finish_success():
                            close_overlay()

                            self.show_notification(
                                "Report Submitted",
                                (
                                    "Your report has been "
                                    "successfully sent to "
                                    "technical support."
                                ),
                                color="#10b981"
                            )

                        self.root.after(
                            0,
                            finish_success
                        )

                    else:
                        try:
                            response_data = (
                                response.json()
                            )

                            msg = (
                                response_data.get(
                                    "message",
                                    "The server could not "
                                    "verify your report."
                                )
                            )

                        except Exception:
                            msg = (
                                "The server could not "
                                "verify your report. "
                                "Please check your "
                                "credentials and try again."
                            )

                        def show_report_error():
                            self.show_notification(
                                "Report Not Sent",
                                msg,
                                color="#ef4444"
                            )

                            try:
                                if (
                                    self.btn_send
                                    and self.btn_send.winfo_exists()
                                ):
                                    self.btn_send.config(
                                        state="normal",
                                        text="SUBMIT REPORT"
                                    )

                            except Exception:
                                pass

                        self.root.after(
                            0,
                            show_report_error
                        )

                except requests.exceptions.Timeout:

                    def show_timeout():
                        self.show_notification(
                            "Request Timed Out",
                            (
                                "The report could not be sent "
                                "because the server took too long "
                                "to respond."
                            ),
                            color="#ef4444"
                        )

                        try:
                            if (
                                self.btn_send
                                and self.btn_send.winfo_exists()
                            ):
                                self.btn_send.config(
                                    state="normal",
                                    text="SUBMIT REPORT"
                                )

                        except Exception:
                            pass

                    self.root.after(
                        0,
                        show_timeout
                    )

                except requests.exceptions.ConnectionError:

                    def show_connection_error():
                        self.show_notification(
                            "Connection Error",
                            (
                                "Could not connect to the "
                                "LabGuard server. Please check "
                                "the network connection and try again."
                            ),
                            color="#ef4444"
                        )

                        try:
                            if (
                                self.btn_send
                                and self.btn_send.winfo_exists()
                            ):
                                self.btn_send.config(
                                    state="normal",
                                    text="SUBMIT REPORT"
                                )

                        except Exception:
                            pass

                    self.root.after(
                        0,
                        show_connection_error
                    )

                except Exception as e:
                    print(
                        f"[REPORT ERROR] {e}"
                    )

                    def show_general_error():
                        self.show_notification(
                            "Submission Failed",
                            (
                                "Something went wrong while "
                                "sending the report. Please try again."
                            ),
                            color="#ef4444"
                        )

                        try:
                            if (
                                self.btn_send
                                and self.btn_send.winfo_exists()
                            ):
                                self.btn_send.config(
                                    state="normal",
                                    text="SUBMIT REPORT"
                                )

                        except Exception:
                            pass

                    self.root.after(
                        0,
                        show_general_error
                    )

            threading.Thread(
                target=async_report,
                daemon=True
            ).start()

        # -------------------------------------------------------------
        # REPORT BUTTONS
        # -------------------------------------------------------------

        btn_container = tk.Frame(
            self.overlay,
            bg="#1e293b"
        )

        btn_container.pack(
            pady=18
        )

        self.btn_send = tk.Button(
            btn_container,
            text="SUBMIT REPORT",
            command=handle_submit,
            bg="#ef4444",
            fg="white",
            font=("Arial", 9, "bold"),
            width=16,
            height=2,
            relief="flat",
            cursor="hand2",
        )

        self.btn_send.pack(
            side="left",
            padx=6
        )

        tk.Button(
            btn_container,
            text="CANCEL",
            command=close_overlay,
            bg="#475569",
            fg="white",
            font=("Arial", 9, "bold"),
            width=12,
            height=2,
            relief="flat",
            cursor="hand2",
        ).pack(
            side="left",
            padx=6
        )

    # =================================================================
    # FORCE WINDOW ON TOP
    # =================================================================

    def force_on_top(self):
        try:
            if self.is_session_active:
                return

            # ---------------------------------------------------------
            # NOTIFICATION GETS FIRST PRIORITY
            # ---------------------------------------------------------

            if (
                self.active_notification
                and self.active_notification.winfo_exists()
            ):
                self.active_notification.lift()

                self.active_notification.attributes(
                    "-topmost",
                    True
                )

            # ---------------------------------------------------------
            # WI-FI MODAL
            # ---------------------------------------------------------

            elif (
                self.wifi_modal
                and self.wifi_modal.winfo_exists()
            ):
                self.wifi_modal.lift()

                self.wifi_modal.attributes(
                    "-topmost",
                    True
                )

            # ---------------------------------------------------------
            # REPORT OVERLAY
            # ---------------------------------------------------------

            elif (
                self.overlay
                and self.overlay.winfo_exists()
            ):
                self.overlay.lift()

                self.overlay.attributes(
                    "-topmost",
                    True
                )
            # ---------------------------------------------------------
            # SHUTDOWN MODAL
            # ---------------------------------------------------------

            elif (
                self.shutdown_modal
                and self.shutdown_modal.winfo_exists()
            ):
                self.shutdown_modal.lift()

                self.shutdown_modal.attributes(
                    "-topmost",
                    True
                )

            # ---------------------------------------------------------
            # LOGIN
            # ---------------------------------------------------------

            else:
                self.root.lift()

                self.root.attributes(
                    "-topmost",
                    True
                )
            

        except tk.TclError:
            pass

        except Exception as e:
            print(
                f"[WINDOW STACK ERROR] {e}"
            )

        try:
            self.root.after(
                1000,
                self.force_on_top
            )

        except Exception:
            pass

    # =================================================================
    # AUTHENTICATION
    # =================================================================

    def attempt_login(self):
        login_credential = (
            self.entry_id.get().strip()
        )

        password = (
            self.entry_password.get()
        )

        if (
            not login_credential
            or not password
        ):
            self.show_notification(
                "Input Required",
                "Enter your ID and account password.",
                color="#D4AF37"
            )

            return

        self.btn_unlock.config(
            state="disabled",
            text="VERIFYING..."
        )

        payload = {
            "pc_number": PC_NUMBER,
            "lab": LAB_ID,
            "student_id": login_credential,
            "password": password,
        }

        def perform_login():
            try:
                session = (
                    get_authenticated_session()
                )

                response = session.post(
                    f"{API_URL}/login",
                    json=payload,
                    timeout=10
                )

                if response.status_code == 200:
                    try:
                        resp_json = (
                            response.json()
                        )

                    except Exception:
                        resp_json = {}

                    user_name = (
                        resp_json.get(
                            "name",
                            "User"
                        )
                    )

                    user_role = str(
                        resp_json.get("role")
                        or resp_json.get(
                            "data",
                            {}
                        ).get(
                            "role",
                            "student"
                        )
                    ).lower()

                    session_id = (
                        resp_json.get(
                            "session_id"
                        )
                        or resp_json.get(
                            "data",
                            {}
                        ).get(
                            "session_id"
                        )
                    )

                    self.current_student = {
                        "id": login_credential,
                        "password": password,
                        "name": user_name,
                        "role": user_role,
                        "session_id": session_id,
                    }

                    staff_roles = [
                        "admin",
                        "super-admin",
                        "personnel",
                        "teacher",
                        "technician",
                        "faculty",
                        "staff"
                    ]

                    if user_role in staff_roles:
                        print(
                            f"[RBAC] Staff role "
                            f"'{user_role}' authenticated. "
                            f"Direct unlock granted."
                        )

                        self.root.after(
                            0,
                            lambda:
                                self._handle_staff_login_success(
                                    user_name,
                                    user_role
                                )
                        )

                    else:
                        self.root.after(
                            0,
                            lambda:
                                self.show_checklist_screen(
                                    user_name
                                )
                        )

                else:
                    try:
                        msg = (
                            response.json().get(
                                "message",
                                "Invalid Credentials."
                            )
                        )

                    except Exception:
                        msg = (
                            f"HTTP Error "
                            f"{response.status_code}"
                        )

                    self.root.after(
                        0,
                        lambda:
                            self.show_notification(
                                "Auth Failed",
                                msg,
                                color="#ef4444"
                            )
                    )

                    self.root.after(
                        0,
                        lambda:
                            self.btn_unlock.config(
                                state="normal",
                                text=(
                                    "AUTHENTICATE & INSPECT"
                                )
                            )
                    )

            except Exception as e:
                print(
                    f"[LOGIN ERROR] {e}"
                )

                self.root.after(
                    0,
                    lambda:
                        self.show_notification(
                            "Connection Error",
                            (
                                "Server unreachable. "
                                "Check your connection."
                            ),
                            color="#ef4444"
                        )
                )

                self.root.after(
                    0,
                    lambda:
                        self.btn_unlock.config(
                            state="normal",
                            text=(
                                "AUTHENTICATE & INSPECT"
                            )
                        )
                )

        threading.Thread(
            target=perform_login,
            daemon=True
        ).start()

    def _handle_staff_login_success(
        self,
        user_name,
        user_role
    ):
        self.login_screen_active = False

        self.show_notification(
            "Staff Access",
            (
                f"Welcome, {user_name} "
                f"({user_role.upper()})!"
            ),
            color="#10b981"
        )

        self.root.after(
            1000,
            self.hide_terminal
        )

    # =================================================================
    # SESSION LIFECYCLE
    # =================================================================

    def hide_terminal(self):
        self.is_session_active = True
        self.login_screen_active = False

        try:
            self.root.attributes(
                "-topmost",
                False
            )

        except Exception:
            pass

        show_taskbar()
        stop_keyboard_hook()

        try:
            self.root.withdraw()

        except Exception:
            pass

        self.start_system_tray()
        self.show_floating_signout_pill()

        threading.Thread(
            target=self.heartbeat_loop,
            daemon=True
        ).start()

    # =================================================================
    # SYSTEM TRAY
    # =================================================================

    def create_tray_image(self):
        img = Image.new(
            "RGBA",
            (64, 64),
            color=(0, 0, 0, 0)
        )

        draw = ImageDraw.Draw(
            img
        )

        draw.ellipse(
            [4, 4, 60, 60],
            fill="#0f172a",
            outline="#D4AF37",
            width=3
        )

        draw.rectangle(
            [22, 22, 42, 42],
            fill="#10b981"
        )

        return img

    def start_system_tray(self):
        if not TRAY_AVAILABLE:
            print(
                "[NOTICE] pystray/PIL not installed. "
                "Desktop floating sign-out pill active."
            )

            return

        def on_signout_click(
            icon,
            item
        ):
            try:
                self.root.after(
                    0,
                    self.request_manual_logout
                )

            except Exception:
                pass

        menu = pystray.Menu(
            pystray.MenuItem(
                f"Station: {PC_NUMBER}",
                lambda: None,
                enabled=False
            ),
            pystray.MenuItem(
                (
                    f"User: "
                    f"{self.current_student['name']} "
                    f"({self.current_student['role'].upper()})"
                ),
                lambda: None,
                enabled=False
            ),
            pystray.Menu.SEPARATOR,
            pystray.MenuItem(
                "Sign Out & Lock PC",
                on_signout_click,
                default=True
            ),
        )

        try:
            self.tray_icon = pystray.Icon(
                "LabGuard",
                self.create_tray_image(),
                f"LabGuard ({PC_NUMBER})",
                menu
            )

            threading.Thread(
                target=self.tray_icon.run,
                daemon=True
            ).start()

        except Exception as e:
            print(
                f"[DEBUG] Tray creation error: {e}"
            )

    def stop_system_tray(self):
        if self.tray_icon:
            try:
                self.tray_icon.stop()

            except Exception:
                pass

            self.tray_icon = None

    # =================================================================
    # FLOATING SIGN-OUT PILL
    # =================================================================

    def show_floating_signout_pill(self):
        if (
            self.floating_pill
            and self.floating_pill.winfo_exists()
        ):
            return

        self.floating_pill = tk.Toplevel()

        self.floating_pill.overrideredirect(
            True
        )

        self.floating_pill.attributes(
            "-topmost",
            True
        )

        self.floating_pill.configure(
            bg="#0f172a"
        )

        screen_w = (
            self.root.winfo_screenwidth()
        )

        p_w = 240
        p_h = 42

        x = (
            screen_w
            - p_w
            - 20
        )

        y = 15

        self.floating_pill.geometry(
            f"{p_w}x{p_h}+{x}+{y}"
        )

        container = tk.Frame(
            self.floating_pill,
            bg="#1e293b",
            highlightbackground="#D4AF37",
            highlightthickness=1
        )

        container.pack(
            fill="both",
            expand=True
        )

        role_label = (
            f"● {PC_NUMBER}"
        )

        tk.Label(
            container,
            text=role_label,
            fg="#10b981",
            bg="#1e293b",
            font=("Arial", 9, "bold")
        ).pack(
            side="left",
            padx=(10, 5)
        )

        btn = tk.Button(
            container,
            text="SIGN OUT",
            command=self.request_manual_logout,
            bg="#ef4444",
            fg="white",
            font=("Arial", 8, "bold"),
            relief="flat",
            padx=10,
            cursor="hand2"
        )

        btn.pack(
            side="right",
            padx=8,
            pady=6
        )

    def hide_floating_signout_pill(self):
        if (
            self.floating_pill
            and self.floating_pill.winfo_exists()
        ):
            try:
                self.floating_pill.destroy()

            except Exception:
                pass

            self.floating_pill = None

    def request_manual_logout(self):
        self.is_session_active = False

        send_logout_signal()

        try:
            self.root.after(
                0,
                self.lock_ui_again
            )

        except Exception:
            self.lock_ui_again()

    def shutdown_pc(self):
        if self.shutdown_modal and self.shutdown_modal.winfo_exists():
            try:
                self.shutdown_modal.lift()
                self.shutdown_modal.attributes("-topmost", True)
                self.shutdown_modal.focus_force()
            except Exception:
                pass
            return

        self.shutdown_modal = tk.Toplevel(self.root)
        self.shutdown_modal.configure(
            bg="#1e293b",
            highlightbackground="#ef4444",
            highlightthickness=2
        )
        self.shutdown_modal.overrideredirect(True)

        width = 460
        height = 230

        screen_w = self.root.winfo_screenwidth()
        screen_h = self.root.winfo_screenheight()

        x = (screen_w // 2) - (width // 2)
        y = (screen_h // 2) - (height // 2)

        self.shutdown_modal.geometry(f"{width}x{height}+{x}+{y}")
        self.shutdown_modal.attributes("-topmost", True)
        self.shutdown_modal.transient(self.root)

        def close_modal(event=None):
            if self.shutdown_modal:
                try:
                    self.shutdown_modal.grab_release()
                except Exception:
                    pass
                try:
                    self.shutdown_modal.destroy()
                except Exception:
                    pass
            self.shutdown_modal = None
            self.restore_login_focus()

        def confirm_and_power_off():
            close_modal()

            print(f"[SHUTDOWN] Shutdown confirmed for {PC_NUMBER}.")
            self.is_session_active = False

            try:
                send_logout_signal()
            except Exception as e:
                print(f"[SHUTDOWN] Release signal failed: {e}")

            try:
                self.stop_system_tray()
            except Exception:
                pass

            try:
                self.hide_floating_signout_pill()
            except Exception:
                pass

            cleanup_security()

            self.root.after(
                300,
                lambda: subprocess.Popen(
                    ["shutdown", "/s", "/t", "0"],
                    creationflags=(
                        subprocess.CREATE_NO_WINDOW
                        if hasattr(subprocess, "CREATE_NO_WINDOW")
                        else 0
                    )
                )
            )

        self.shutdown_modal.protocol("WM_DELETE_WINDOW", close_modal)
        self.shutdown_modal.bind("<Escape>", close_modal)

        # Header Title
        tk.Label(
            self.shutdown_modal,
            text="SHUT DOWN WORKSTATION",
            fg="#ef4444",
            bg="#1e293b",
            font=("Arial Black", 15),
        ).pack(pady=(24, 6))

        # Station & Warning Details
        tk.Label(
            self.shutdown_modal,
            text=(
                f"Are you sure you want to turn off {PC_NUMBER} ({LAB_ID})?\n"
                "The workstation will be released and powered off immediately."
            ),
            fg="#cbd5e1",
            bg="#1e293b",
            font=("Arial", 10),
            justify="center",
            wraplength=400,
        ).pack(pady=(0, 20))

        # Action Buttons
        btn_box = tk.Frame(self.shutdown_modal, bg="#1e293b")
        btn_box.pack()

        tk.Button(
            btn_box,
            text="⏻ SHUT DOWN",
            command=confirm_and_power_off,
            bg="#ef4444",
            fg="white",
            activebackground="#dc2626",
            activeforeground="white",
            font=("Arial", 9, "bold"),
            width=16,
            height=2,
            relief="flat",
            cursor="hand2",
        ).pack(side="left", padx=8)

        tk.Button(
            btn_box,
            text="CANCEL",
            command=close_modal,
            bg="#475569",
            fg="white",
            activebackground="#64748b",
            activeforeground="white",
            font=("Arial", 9, "bold"),
            width=12,
            height=2,
            relief="flat",
            cursor="hand2",
        ).pack(side="left", padx=8)

        try:
            self.shutdown_modal.grab_set()
        except Exception:
            pass

        self.shutdown_modal.lift()
        self.shutdown_modal.focus_force()

    # =================================================================
    # HEARTBEAT
    # =================================================================

    def heartbeat_loop(self):
        time.sleep(10)

        consecutive_failures = 0

        while self.is_session_active:
            try:
                lab_param = urllib.parse.quote(
                    str(LAB_ID)
                )

                pc_param = urllib.parse.quote(
                    str(PC_NUMBER)
                )

                url = (
                    f"{API_URL}/status/"
                    f"{lab_param}/"
                    f"{pc_param}"
                )

                session = (
                    get_authenticated_session()
                )

                response = session.get(
                    url,
                    timeout=5
                )

                if response.status_code == 200:
                    consecutive_failures = 0

                    try:
                        data = response.json()

                    except Exception:
                        data = {}

                    pc_status = (
                        data.get("status")
                        or data.get(
                            "data",
                            {}
                        ).get("status")
                    )

                    if (
                        pc_status
                        and str(
                            pc_status
                        ).lower()
                        in [
                            "released",
                            "maintenance"
                        ]
                    ):
                        self.root.after(
                            0,
                            self.lock_ui_again
                        )

                        break

            except Exception as e:
                consecutive_failures += 1

                print(
                    f"[HEARTBEAT] Failure "
                    f"{consecutive_failures}: {e}"
                )

                if (
                    consecutive_failures
                    >= 5
                ):
                    self.root.after(
                        0,
                        self.lock_ui_again
                    )

                    break

            time.sleep(5)

    # =================================================================
    # LOCK UI AGAIN
    # =================================================================

    def lock_ui_again(self):
        self.is_session_active = False
        self.login_screen_active = True

        self.stop_system_tray()

        self.hide_floating_signout_pill()

        self.current_student = {
            "id": "",
            "password": "",
            "name": "",
            "role": "student",
            "session_id": None
        }

        try:
            self.entry_id.delete(
                0,
                tk.END
            )

            self.entry_password.delete(
                0,
                tk.END
            )

            self.btn_unlock.config(
                state="normal",
                text="AUTHENTICATE & INSPECT"
            )

            self.checklist_view.pack_forget()

            self.login_view.pack()

            hide_taskbar()
            start_keyboard_hook()

            self.root.deiconify()

            self.root.lift()

            self.root.attributes(
                "-topmost",
                True
            )

            self.root.update_idletasks()

            self.root.after(
                100,
                self.restore_login_focus
            )

        except tk.TclError:
            pass

        except Exception as e:
            print(
                f"[LOCK UI ERROR] {e}"
            )

    # =================================================================
    # EMERGENCY ADMIN EXIT
    # =================================================================

    def emergency_admin_exit(
        self,
        event=None
    ):
        print(
            "[ADMIN] Emergency exit invoked. "
            "Restoring full workstation control..."
        )

        self.stop_system_tray()

        self.hide_floating_signout_pill()

        try:
            if (
                self.active_notification
                and self.active_notification.winfo_exists()
            ):
                self.active_notification.destroy()

        except Exception:
            pass

        try:
            if (
                self.wifi_modal
                and self.wifi_modal.winfo_exists()
            ):
                try:
                    self.wifi_modal.grab_release()

                except Exception:
                    pass

                try:
                    self.wifi_modal.focus_release()

                except Exception:
                    pass

                self.wifi_modal.destroy()

                self.wifi_modal = None

        except Exception:
            pass

        cleanup_security()

        try:
            self.root.destroy()

        except Exception:
            pass

        sys.exit(0)


# =====================================================================
# 8. APPLICATION ENTRYPOINT
# =====================================================================

if __name__ == "__main__":
    app_root = tk.Tk()

    client = LabGuardClient(
        app_root
    )

    app_root.update()

    app_root.mainloop()