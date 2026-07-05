import tkinter as tk
from tkinter import messagebox
import requests
import threading
import time
import atexit
import urllib3
import signal
import sys

# Disable SSL warnings for local development (.test domains)
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# --- CONFIGURATION ---
API_URL = "https://labguard.test/api/pc"
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
        self.root.title("LabGuard Terminal")
        self.root.attributes("-fullscreen", True)
        self.root.attributes("-topmost", True)
        self.root.configure(bg='#0f172a') 
        self.root.protocol("WM_DELETE_WINDOW", lambda: None) 

        self.frame = tk.Frame(self.root, bg='#0f172a')
        self.frame.place(relx=0.5, rely=0.5, anchor='center')

        tk.Label(self.frame, text="LABGUARD", fg='#D4AF37', bg='#0f172a', font=("Arial Black", 50)).pack()
        tk.Label(self.frame, text=f"STATION: {PC_NUMBER}", fg='#64748b', bg='#0f172a', font=("Arial", 12, "bold")).pack(pady=5)

        tk.Label(self.frame, text="STUDENT NUMBER", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(30, 0))
        self.entry_id = tk.Entry(self.frame, font=("Arial", 18), justify='center', width=25, bg='#1e293b', fg='white', insertbackground='white', border=0)
        self.entry_id.pack(pady=5, ipady=10)

        tk.Label(self.frame, text="ACCOUNT PASSWORD", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(15, 0))
        self.entry_password = tk.Entry(self.frame, font=("Arial", 18), justify='center', width=25, show="*", bg='#1e293b', fg='white', insertbackground='white', border=0)
        self.entry_password.pack(pady=5, ipady=10)
        
        self.entry_id.focus_set()

        self.btn_unlock = tk.Button(self.frame, text="UNLOCK STATION", command=self.attempt_login, bg='#D4AF37', fg='white', font=("Arial", 12, "bold"), width=30, height=2, cursor="hand2", relief="flat")
        self.btn_unlock.pack(pady=30)

        self.report_trigger = tk.Label(self.root, text="⚠ HAVE A PROBLEM WITH THIS PC? CLICK HERE TO REPORT", 
                                       fg='#ef4444', bg='#0f172a', font=("Arial", 8, "bold"), cursor="hand2")
        self.report_trigger.pack(side="bottom", pady=40)
        self.report_trigger.bind("<Button-1>", lambda e: self.open_report_overlay())

        self.force_on_top()

    def open_report_overlay(self):
        self.root.attributes("-topmost", False)
        self.overlay = tk.Toplevel(self.root)
        self.overlay.configure(bg='#1e293b')
        self.overlay.overrideredirect(True) 

        # Extended layout dimensions to fit credentials elegantly
        width, height = 500, 640
        screen_w = self.root.winfo_screenwidth()
        screen_h = self.root.winfo_screenheight()
        x = (screen_w // 2) - (width // 2)
        y = (screen_h // 2) - (height // 2)
        self.overlay.geometry(f"{width}x{height}+{x}+{y}")

        self.overlay.attributes("-topmost", True)
        self.overlay.grab_set() 

        tk.Label(self.overlay, text="REPORT AN ISSUE", fg='#D4AF37', bg='#1e293b', font=("Arial Black", 16)).pack(pady=(25, 5))
        
        # Accountability Identity Sub-form
        tk.Label(self.overlay, text="Confirm Student Number", fg='white', bg='#1e293b', font=("Arial", 9, "bold")).pack(anchor="w", padx=60, pady=(15, 2))
        self.report_student_id = tk.Entry(self.overlay, font=("Arial", 12), bg='#0f172a', fg='white', border=0, insertbackground='white')
        self.report_student_id.pack(fill="x", padx=60, ipady=6)
        
        tk.Label(self.overlay, text="Confirm Password", fg='white', bg='#1e293b', font=("Arial", 9, "bold")).pack(anchor="w", padx=60, pady=(10, 2))
        self.report_password = tk.Entry(self.overlay, font=("Arial", 12), show="*", bg='#0f172a', fg='white', border=0, insertbackground='white')
        self.report_password.pack(fill="x", padx=60, ipady=6)

        # Issue Metadata fields
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
            
            # Pack payload exactly with attributes requested by API validate schema
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
        if not hasattr(self, 'overlay') or not self.overlay.winfo_exists():
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
            CinematicNotify(self.root, "Error", "Server is offline.", color="#ef4444")
            self.btn_unlock.config(state="normal", text="UNLOCK STATION")

    def hide_terminal(self):
        self.root.withdraw()
        threading.Thread(target=self.heartbeat_loop, daemon=True).start()

    def heartbeat_loop(self):
        """Checks if a teacher or admin remotely locked the PC."""
        while True:
            try:
                response = requests.get(f"{API_URL}/status/{PC_NUMBER}", headers=HEADERS, timeout=5, verify=False)
                if response.status_code == 200 and response.json().get('status') == 'available':
                    self.root.after(0, self.lock_ui_again)
                    break
            except Exception: pass 
            time.sleep(15) 

    def lock_ui_again(self):
        self.entry_id.delete(0, tk.END)
        self.entry_password.delete(0, tk.END)
        self.btn_unlock.config(state="normal", text="UNLOCK STATION")
        self.root.deiconify()
        self.entry_id.focus_set()

if __name__ == "__main__":
    app_root = tk.Tk()
    client = LabGuardClient(app_root)
    app_root.mainloop()