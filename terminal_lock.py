import tkinter as tk
from tkinter import ttk, messagebox
import requests
import threading
import time
import atexit
import urllib3
import signal
import sys
import subprocess
import re

# Disable SSL warnings for local development (.test domains)
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# --- CONFIGURATION ---
API_URL = "https://labguard.test/api/pc"
LAB_ID = "1"        # Lab ID, Code, or Name matching your database records
PC_NUMBER = "PC-01" 
HEADERS = {"Host": "labguard.test", "Accept": "application/json"}
# ---------------------

def send_logout_signal():
    """Tells Laravel to release this PC and set time_out."""
    try:
        requests.post(f"{API_URL}/logout", 
                      json={"pc_number": PC_NUMBER}, 
                      headers=HEADERS, 
                      timeout=3, 
                      verify=False)
        print(f"Signal Sent: {PC_NUMBER} has been released.")
    except Exception as e:
        print(f"Logout signal failed: {e}")

def handle_exit_signal(sig, frame):
    """Handles OS-level termination signals."""
    print("Force shutdown detected...")
    send_logout_signal()
    sys.exit(0)

# Register shutdown handlers
atexit.register(send_logout_signal)
signal.signal(signal.SIGINT, handle_exit_signal)
signal.signal(signal.SIGTERM, handle_exit_signal)


class CinematicNotify(tk.Toplevel):
    """Custom glassmorphic notification overlay with Fade-Out."""
    def __init__(self, parent, title, message, color="#D4AF37"):
        super().__init__(parent)
        self.overrideredirect(True)
        self.attributes("-topmost", True)
        self.attributes("-alpha", 1.0)
        self.configure(bg='#1e293b', highlightbackground=color, highlightthickness=2)
        
        p_w = parent.winfo_screenwidth()
        p_h = parent.winfo_screenheight()
        
        width, height = 400, 160
        x = (p_w // 2) - (width // 2)
        y = (p_h // 2) - (height // 2)
        self.geometry(f"{width}x{height}+{x}+{y}")

        tk.Label(self, text=title.upper(), fg=color, bg='#1e293b', 
                 font=("Arial Black", 14)).pack(pady=(25, 5))
        
        tk.Label(self, text=message, fg='white', bg='#1e293b', 
                 font=("Arial", 10), wraplength=340).pack(pady=5)
        
        self.progress_bg = tk.Frame(self, bg='#0f172a', height=4)
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


class LabGuardClient:
    def __init__(self, root):
        self.root = root
        self.is_session_active = False
        self.is_maintenance_mode = False

        self.root.title("LabGuard Terminal")
        self.root.attributes("-fullscreen", True)
        self.root.attributes("-topmost", True)
        self.root.configure(bg='#0f172a') 
        self.root.protocol("WM_DELETE_WINDOW", lambda: None) 

        # Top Bar for Network Status
        self.top_bar = tk.Frame(self.root, bg='#0f172a')
        self.top_bar.pack(side="top", fill="x", padx=20, pady=20)

        self.net_indicator = tk.Label(
            self.top_bar, 
            text="● CONNECTING...", 
            fg='#eab308', 
            bg='#0f172a', 
            font=("Arial", 10, "bold"),
            cursor="hand2"
        )
        self.net_indicator.pack(side="right", padx=30)
        self.net_indicator.bind("<Button-1>", lambda e: self.open_wifi_modal())

        # Main Center Container (Holds Login Form & Maintenance Screen)
        self.frame = tk.Frame(self.root, bg='#0f172a')
        self.frame.place(relx=0.5, rely=0.5, anchor='center')

        # Header Section
        tk.Label(self.frame, text="LABGUARD", fg='#D4AF37', bg='#0f172a', font=("Arial Black", 50)).pack()
        tk.Label(self.frame, text=f"STATION: {PC_NUMBER}", fg='#64748b', bg='#0f172a', font=("Arial", 12, "bold")).pack(pady=5)

        # Standard Login Widgets Frame
        self.login_form_frame = tk.Frame(self.frame, bg='#0f172a')
        self.login_form_frame.pack(pady=10)

        tk.Label(self.login_form_frame, text="STUDENT NUMBER", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(20, 0))
        self.entry_id = tk.Entry(
            self.login_form_frame,
            font=("Arial", 18),
            justify='center',
            width=25,
            bg='#1e293b',
            fg='white',
            insertbackground='white',
            border=0
        )
        self.entry_id.pack(pady=5, ipady=10)
        self.entry_id.bind("<Key>", self._filter_student_id_key)
        self.entry_id.bind("<KeyRelease>", self._format_student_id_entry)

        tk.Label(self.login_form_frame, text="ACCOUNT PASSWORD", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(15, 0))
        self.entry_password = tk.Entry(self.login_form_frame, font=("Arial", 18), justify='center', width=25, show="*", bg='#1e293b', fg='white', insertbackground='white', border=0)
        self.entry_password.pack(pady=5, ipady=10)
        
        self.entry_id.focus_set()

        self.btn_unlock = tk.Button(self.login_form_frame, text="UNLOCK STATION", command=self.attempt_login, bg='#D4AF37', fg='white', font=("Arial", 12, "bold"), width=30, height=2, cursor="hand2", relief="flat")
        self.btn_unlock.pack(pady=25)

        # Maintenance Frame (Hidden by default)
        self.maintenance_frame = tk.Frame(self.frame, bg='#0f172a')

        tk.Label(self.maintenance_frame, text="🔧", fg='#f59e0b', bg='#0f172a', font=("Arial", 50)).pack(pady=(20, 5))
        tk.Label(self.maintenance_frame, text="STATION UNDER MAINTENANCE", fg='#f59e0b', bg='#0f172a', font=("Arial Black", 18)).pack(pady=5)
        tk.Label(self.maintenance_frame, text="This computer is currently offline for system updates or hardware repair.\nPlease use another available terminal.", 
                 fg='#94a3b8', bg='#0f172a', font=("Arial", 11), justify="center", wraplength=450).pack(pady=10)

        # Bottom Trigger Options
        self.bottom_bar = tk.Frame(self.root, bg='#0f172a')
        self.bottom_bar.pack(side="bottom", pady=30)

        self.report_trigger = tk.Label(self.bottom_bar, text="⚠ HAVE A PROBLEM WITH THIS PC? CLICK HERE TO REPORT", 
                                       fg='#ef4444', bg='#0f172a', font=("Arial", 8, "bold"), cursor="hand2")
        self.report_trigger.pack()
        self.report_trigger.bind("<Button-1>", lambda e: self.open_report_overlay())

        self.force_on_top()

        # Start Async Network Monitoring Daemon Thread
        threading.Thread(target=self.network_monitor_loop, daemon=True).start()

    def _filter_student_id_key(self, event):
        """Block letters and keep only digits in the student ID field."""
        if event.keysym in {'BackSpace', 'Delete', 'Tab', 'Return', 'Left', 'Right', 'Up', 'Down', 'Home', 'End'}:
            return

        if event.char and not event.char.isdigit():
            return 'break'

    def _format_student_id_entry(self, event=None):
        """Auto-format the student ID as xx-xxxx-xxxxxx as the user types."""
        target = event.widget if event else self.entry_id
        current_value = target.get().strip()
        digits = re.sub(r"\D", "", current_value)[:12]
        formatted = self._format_student_id_text(digits)

        if formatted != current_value:
            target.delete(0, tk.END)
            target.insert(0, formatted)
            target.icursor(len(formatted))

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
                res = requests.get(f"{API_URL}/status/{LAB_ID}/{PC_NUMBER}", headers=HEADERS, timeout=3, verify=False)
                if res.status_code == 200:
                    data = res.json()
                    pc_status = data.get('status') or data.get('data', {}).get('status')
                    
                    self.root.after(0, self.update_net_status, True)

                    # Check if backend set the PC to maintenance mode
                    if pc_status and str(pc_status).lower() == 'maintenance':
                        self.root.after(0, self.show_maintenance_ui)
                    else:
                        self.root.after(0, self.restore_login_ui)

                elif res.status_code == 404:
                    self.root.after(0, self.update_net_status, True)
                    self.root.after(0, self.restore_login_ui)
                else:
                    self.root.after(0, self.update_net_status, False)
            except Exception:
                self.root.after(0, self.update_net_status, False)
            time.sleep(5)

    def update_net_status(self, is_online):
        """Updates the status text and color on top bar."""
        if is_online:
            self.net_indicator.config(text="● ONLINE", fg='#10b981')
        else:
            self.net_indicator.config(text="▲ OFFLINE (CLICK TO FIX WI-FI)", fg='#ef4444')

    def open_wifi_modal(self):
        """Glassmorphic UI overlay for scanning and connecting to Wi-Fi networks via netsh."""
        self.root.attributes("-topmost", False)
        self.wifi_modal = tk.Toplevel(self.root)
        self.wifi_modal.configure(bg='#1e293b')
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
            """Safely closes the modal and restores topmost lock on main screen."""
            self.wifi_modal.grab_release()
            self.wifi_modal.destroy()
            if hasattr(self, 'wifi_modal'):
                del self.wifi_modal
            self.root.attributes("-topmost", True)

        self.wifi_modal.bind("<Escape>", close_wifi)

        header_frame = tk.Frame(self.wifi_modal, bg='#1e293b')
        header_frame.pack(fill="x", padx=15, pady=(15, 0))

        tk.Label(header_frame, text="NETWORK SETTINGS", fg='#D4AF37', bg='#1e293b', 
                 font=("Arial Black", 14)).pack(side="left")

        btn_x = tk.Button(header_frame, text=" ✕ ", command=close_wifi, bg='#1e293b', fg='#94a3b8', 
                          activebackground='#ef4444', activeforeground='white',
                          font=("Arial", 12, "bold"), border=0, cursor="hand2")
        btn_x.pack(side="right")

        tk.Label(self.wifi_modal, text="Select an available Wi-Fi access point to connect.", 
                 fg='#94a3b8', bg='#1e293b', font=("Arial", 9)).pack(anchor="w", padx=15, pady=(2, 10))

        list_frame = tk.Frame(self.wifi_modal, bg='#0f172a')
        list_frame.pack(fill="both", expand=True, padx=20, pady=5)

        self.wifi_listbox = tk.Listbox(list_frame, bg='#0f172a', fg='white', font=("Arial", 11), 
                                       selectbackground='#D4AF37', borderwidth=0, highlightthickness=0)
        self.wifi_listbox.pack(side="left", fill="both", expand=True, padx=5, pady=5)

        tk.Label(self.wifi_modal, text="Security Key / Password", fg='white', bg='#1e293b', 
                 font=("Arial", 9, "bold")).pack(anchor="w", padx=20, pady=(10, 2))
        self.wifi_pass = tk.Entry(self.wifi_modal, font=("Arial", 12), show="*", bg='#0f172a', 
                                  fg='white', border=0, insertbackground='white')
        self.wifi_pass.pack(fill="x", padx=20, pady=5, ipady=6)

        btn_frame = tk.Frame(self.wifi_modal, bg='#1e293b')
        btn_frame.pack(pady=20)

        tk.Button(btn_frame, text="SCAN WI-FI", command=self.scan_wifi_networks, bg='#3b82f6', fg='white', 
                  font=("Arial", 9, "bold"), width=12, height=2, relief="flat", cursor="hand2").pack(side="left", padx=5)
        
        tk.Button(btn_frame, text="CONNECT", command=self.connect_to_wifi, bg='#10b981', fg='white', 
                  font=("Arial", 9, "bold"), width=12, height=2, relief="flat", cursor="hand2").pack(side="left", padx=5)

        tk.Button(btn_frame, text="CLOSE", command=close_wifi, bg='#475569', fg='white', 
                  font=("Arial", 9, "bold"), width=10, height=2, relief="flat", cursor="hand2").pack(side="left", padx=5)

        self.scan_wifi_networks()

    def scan_wifi_networks(self):
        """Forces Wi-Fi interface ON, then scans nearby SSIDs using Windows netsh."""
        self.wifi_listbox.delete(0, tk.END)
        self.wifi_listbox.insert(tk.END, "Turning on Wi-Fi adapter & scanning...")

        def execute_scan():
            try:
                subprocess.run('netsh interface set interface "Wi-Fi" admin=enabled', shell=True, capture_output=True)
                time.sleep(1)

                output = subprocess.check_output("netsh wlan show networks", shell=True, stderr=subprocess.STDOUT).decode('utf-8', errors='ignore')
                ssids = re.findall(r"SSID\s+\d+\s+:\s+(.*)", output)
                
                self.wifi_listbox.delete(0, tk.END)
                clean_ssids = [s.strip() for s in ssids if s.strip()]
                
                if clean_ssids:
                    for ssid in set(clean_ssids):
                        self.wifi_listbox.insert(tk.END, ssid)
                else:
                    self.wifi_listbox.delete(0, tk.END)
                    self.wifi_listbox.insert(tk.END, "No networks found.")
            except Exception:
                self.wifi_listbox.delete(0, tk.END)
                self.wifi_listbox.insert(tk.END, "Error scanning Wi-Fi interfaces.")

        threading.Thread(target=execute_scan, daemon=True).start()

    def connect_to_wifi(self):
        """Generates an automated XML profile and connects Windows to chosen SSID."""
        try:
            selected_ssid = self.wifi_listbox.get(self.wifi_listbox.curselection())
        except Exception:
            CinematicNotify(self.wifi_modal, "Selection Required", "Please click an SSID from the list.", color="#ef4444")
            return

        password = self.wifi_pass.get().strip()

        def execute_connection():
            profile_xml = f"""<?xml version="1.0"?>
<WLANProfile xmlns="http://www.microsoft.com/networking/WLAN/profile/v1">
    <name>{selected_ssid}</name>
    <SSIDConfig><SSID><name>{selected_ssid}</name></SSID></SSIDConfig>
    <connectionType>ESS</connectionType>
    <connectionMode>auto</connectionMode>
    <MSM>
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
        </security>
    </MSM>
</WLANProfile>"""
            
            try:
                filename = f"wifi_{selected_ssid}.xml"
                with open(filename, "w") as f:
                    f.write(profile_xml)

                subprocess.run(f'netsh wlan add profile filename="{filename}"', shell=True, check=True)
                subprocess.run(f'netsh wlan connect name="{selected_ssid}"', shell=True, check=True)

                CinematicNotify(self.wifi_modal, "Connecting", f"Connecting to {selected_ssid}...", color="#3b82f6")
            except Exception:
                CinematicNotify(self.wifi_modal, "Wi-Fi Error", "Could not establish connection profile.", color="#ef4444")

        threading.Thread(target=execute_connection, daemon=True).start()

    # --- REPORTING OVERLAY & LOGIN HANDLERS ---

    def open_report_overlay(self):
        self.root.attributes("-topmost", False)
        self.overlay = tk.Toplevel(self.root)
        self.overlay.configure(bg='#1e293b')
        self.overlay.overrideredirect(True) 

        width, height = 500, 640
        screen_w = self.root.winfo_screenwidth()
        screen_h = self.root.winfo_screenheight()
        x = (screen_w // 2) - (width // 2)
        y = (screen_h // 2) - (height // 2)
        self.overlay.geometry(f"{width}x{height}+{x}+{y}")

        self.overlay.attributes("-topmost", True)
        self.overlay.grab_set() 

        tk.Label(self.overlay, text="REPORT AN ISSUE", fg='#D4AF37', bg='#1e293b', font=("Arial Black", 16)).pack(pady=(25, 5))
        
        tk.Label(self.overlay, text="Confirm Student Number", fg='white', bg='#1e293b', font=("Arial", 9, "bold")).pack(anchor="w", padx=60, pady=(15, 2))
        self.report_student_id = tk.Entry(
            self.overlay,
            font=("Arial", 12),
            bg='#0f172a',
            fg='white',
            border=0,
            insertbackground='white'
        )
        self.report_student_id.pack(fill="x", padx=60, ipady=6)
        self.report_student_id.bind("<Key>", self._filter_student_id_key)
        self.report_student_id.bind("<KeyRelease>", self._format_student_id_entry)
        
        tk.Label(self.overlay, text="Confirm Password", fg='white', bg='#1e293b', font=("Arial", 9, "bold")).pack(anchor="w", padx=60, pady=(10, 2))
        self.report_password = tk.Entry(self.overlay, font=("Arial", 12), show="*", bg='#0f172a', fg='white', border=0, insertbackground='white')
        self.report_password.pack(fill="x", padx=60, ipady=6)

        tk.Label(self.overlay, text="Select Category", fg='white', bg='#1e293b', font=("Arial", 9, "bold")).pack(anchor="w", padx=60, pady=(15, 2))
        self.issue_var = tk.StringVar(value="Hardware Issue")
        
        dropdown = tk.OptionMenu(self.overlay, self.issue_var, "Hardware Issue", "Software/App Error", "No Internet", "Peripheral (Mouse/KB)")
        dropdown.config(bg="#0f172a", fg="white", activebackground="#D4AF37", font=("Arial", 10), relief="flat", borderwidth=0)
        dropdown.pack(fill="x", padx=60)

        tk.Label(self.overlay, text="Describe the Problem", fg='white', bg='#1e293b', font=("Arial", 9, "bold")).pack(anchor="w", padx=60, pady=(15, 2))
        self.remarks_box = tk.Text(self.overlay, height=4, font=("Arial", 11), bg='#0f172a', fg='white', border=0, padx=15, pady=10, insertbackground='white')
        self.remarks_box.pack(padx=60, fill="x")
        
        self.report_student_id.focus_set()

        def close_overlay():
            self.overlay.grab_release()
            self.overlay.destroy()
            if hasattr(self, 'overlay'):
                del self.overlay
            self.root.attributes("-topmost", True)

        def handle_submit():
            student_id = self.report_student_id.get().strip()
            password = self.report_password.get()
            remarks = self.remarks_box.get("1.0", tk.END).strip()

            if not student_id or not password:
                CinematicNotify(self.overlay, "Identity Required", "Credentials required to verify report authenticity.", color="#ef4444")
                return
            if not remarks:
                CinematicNotify(self.overlay, "Incomplete", "Please detail the issue descriptions.", color="#ef4444")
                return

            self.btn_send.config(state="disabled", text="VERIFYING & SENDING...")
            
            payload = {
                "pc_number": PC_NUMBER, 
                "student_id": student_id,
                "password": password,
                "issue_type": self.issue_var.get(), 
                "remarks": remarks
            }

            try:
                response = requests.post(f"{API_URL}/alerts", json=payload, headers=HEADERS, verify=False, timeout=8)
                
                if response.status_code in [200, 201]:
                    CinematicNotify(self.root, "Report Logged", "Identity verified and ticket created.", color="#10b981")
                    close_overlay()
                else:
                    msg = response.json().get('message', 'Identity verification failed.')
                    CinematicNotify(self.overlay, "Auth Failure", msg, color="#ef4444")
                    self.btn_send.config(state="normal", text="SEND REPORT")
            except Exception:
                CinematicNotify(self.overlay, "Connection Error", "Could not connect to database server.", color="#ef4444")
                self.btn_send.config(state="normal", text="SEND REPORT")

        btn_container = tk.Frame(self.overlay, bg='#1e293b')
        btn_container.pack(pady=25)
        
        self.btn_send = tk.Button(btn_container, text="SEND REPORT", command=handle_submit, bg='#ef4444', fg='white', font=("Arial", 9, "bold"), width=18, height=2, relief="flat")
        self.btn_send.pack(side="left", padx=10)
        
        tk.Button(btn_container, text="CANCEL", command=close_overlay, bg='#475569', fg='white', font=("Arial", 9, "bold"), width=15, height=2, relief="flat").pack(side="left", padx=10)

    def force_on_top(self):
        if not hasattr(self, 'overlay') and not hasattr(self, 'wifi_modal'):
            self.root.lift()
            self.root.attributes("-topmost", True)
        self.root.after(2000, self.force_on_top)

    def attempt_login(self):
        student_id = self.entry_id.get().strip()
        password = self.entry_password.get()

        if not student_id or not password:
            CinematicNotify(self.root, "Input Required", "Enter your credentials.", color="#D4AF37")
            return

        self.btn_unlock.config(state="disabled", text="VERIFYING...")
        payload = {"pc_number": PC_NUMBER, "student_id": student_id, "password": password}

        try:
            response = requests.post(f"{API_URL}/login", json=payload, headers=HEADERS, timeout=10, verify=False)
            if response.status_code == 200:
                name = response.json().get('name', 'User')
                CinematicNotify(self.root, "Authorized", f"Welcome, {name}!", color="#10b981")
                self.root.after(1500, self.hide_terminal)
            else:
                msg = response.json().get('message', 'Invalid Credentials.')
                CinematicNotify(self.root, "Auth Failed", msg, color="#ef4444")
                self.btn_unlock.config(state="normal", text="UNLOCK STATION")
        except Exception:
            CinematicNotify(self.root, "Error", "Server is offline. Check Wi-Fi connection.", color="#ef4444")
            self.btn_unlock.config(state="normal", text="UNLOCK STATION")

    def hide_terminal(self):
        self.root.withdraw()
        threading.Thread(target=self.heartbeat_loop, daemon=True).start()

    def heartbeat_loop(self):
        while True:
            try:
                url = f"{API_URL}/status/{LAB_ID}/{PC_NUMBER}"
                response = requests.get(url, headers=HEADERS, timeout=5, verify=False)
                    
                if response.status_code == 200:
                    data = response.json()
                    pc_status = data.get('status') or data.get('data', {}).get('status')
                    
                    if pc_status and str(pc_status).lower() in ['available', 'unoccupied', 'released', 'offline', 'maintenance']:
                        self.root.after(0, self.lock_ui_again)
                        break
            except Exception as e:
                print(f"[DEBUG] Heartbeat Error: {e}")
                
            time.sleep(5)

    def lock_ui_again(self):
        """Restores the UI and resets entry inputs on thread-safe main thread."""
        self.entry_id.delete(0, tk.END)
        self.entry_password.delete(0, tk.END)
        self.btn_unlock.config(state="normal", text="UNLOCK STATION")
        
        self.root.deiconify()
        self.root.lift()
        self.root.attributes("-topmost", True)


if __name__ == "__main__":
    app_root = tk.Tk()
    client = LabGuardClient(app_root)
    app_root.mainloop()