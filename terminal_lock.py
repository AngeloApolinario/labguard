import tkinter as tk
from tkinter import messagebox
import requests
import threading
import time
import atexit
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# --- CONFIGURATION ---
API_URL = "https://labguard.test/api/pc"
PC_NUMBER = "PC-01" 
HEADERS = {"Host": "labguard.test", "Accept": "application/json"}
# ---------------------

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

        tk.Label(self.frame, text="STUDENT NUMBER (00-0000-000000)", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(30, 0))
        self.entry_id = tk.Entry(self.frame, font=("Arial", 18), justify='center', width=25)
        self.entry_id.pack(pady=5, ipady=5)

        tk.Label(self.frame, text="ACCOUNT PASSWORD", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(15, 0))
        self.entry_password = tk.Entry(self.frame, font=("Arial", 18), justify='center', width=25, show="*")
        self.entry_password.pack(pady=5, ipady=5)
        
        self.entry_id.focus_set()

        self.btn = tk.Button(self.frame, text="UNLOCK STATION", command=self.attempt_login, bg='#D4AF37', fg='white', font=("Arial", 12, "bold"), width=30, height=2, cursor="hand2")
        self.btn.pack(pady=30)
        self.force_on_top()

    def force_on_top(self):
        self.root.lift()
        self.root.attributes("-topmost", True)
        self.root.after(2000, self.force_on_top)

    def attempt_login(self):
        student_id = self.entry_id.get().strip()
        password = self.entry_password.get()

        if not student_id or not password:
            messagebox.showwarning("Input Required", "Fill in all fields.")
            return

        payload = {"pc_number": PC_NUMBER, "student_id": student_id, "password": password}

        try:
            response = requests.post(f"{API_URL}/login", json=payload, headers=HEADERS, timeout=10, verify=False)
            if response.status_code == 200:
                messagebox.showinfo("Success", f"Welcome, {response.json().get('name')}!")
                self.root.withdraw() 
                threading.Thread(target=self.heartbeat_loop, daemon=True).start()
            else:
                msg = response.json().get('message', 'Access Denied.')
                messagebox.showerror("Auth Error", msg)
        except Exception as e:
            messagebox.showerror("Connection Error", f"Server unreachable: {e}")

    def heartbeat_loop(self):
        while True:
            try:
                response = requests.get(f"{API_URL}/status/{PC_NUMBER}", headers=HEADERS, timeout=5, verify=False)
                if response.status_code == 200 and response.json().get('status') == 'locked':
                    self.root.after(0, self.lock_ui_again)
                    break
            except: pass 
            time.sleep(15) 

    def lock_ui_again(self):
        self.entry_id.delete(0, tk.END); self.entry_password.delete(0, tk.END)
        self.root.deiconify(); self.entry_id.focus_set()

def on_shutdown():
    try: requests.post(f"{API_URL}/logout", json={"pc_number": PC_NUMBER}, headers=HEADERS, timeout=2, verify=False)
    except: pass

atexit.register(on_shutdown)

if __name__ == "__main__":
    app_root = tk.Tk(); client = LabGuardClient(app_root); app_root.mainloop()