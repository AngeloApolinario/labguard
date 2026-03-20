import tkinter as tk
from tkinter import messagebox
import requests
import threading
import time
import atexit

# --- CONFIGURATION ---
API_URL = "https://labguard.test/api/pc" 
PC_NUMBER = "PC-01"  
LAB_NAME = "Lab 1"
# ---------------------

class LabGuardClient:
    def __init__(self, root):
        self.root = root
        self.root.title("LabGuard Terminal")
        
        # Window Security
        self.root.attributes("-fullscreen", True)
        self.root.attributes("-topmost", True)
        self.root.configure(bg='#0f172a') 
        self.root.protocol("WM_DELETE_WINDOW", lambda: None)

        # UI Layout
        self.frame = tk.Frame(self.root, bg='#0f172a')
        self.frame.place(relx=0.5, rely=0.5, anchor='center')

        tk.Label(self.frame, text="LABGUARD", fg='#D4AF37', bg='#0f172a', font=("Arial Black", 50)).pack()
        tk.Label(self.frame, text=f"STATION: {PC_NUMBER}", fg='#64748b', bg='#0f172a', font=("Arial", 12, "bold")).pack(pady=5)

        # Name Field
        tk.Label(self.frame, text="FULL NAME", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(30, 0))
        self.entry_name = tk.Entry(self.frame, font=("Arial", 18), justify='center', width=20)
        self.entry_name.pack(pady=5, ipady=5)

        # ID Field
        tk.Label(self.frame, text="STUDENT ID", fg='white', bg='#0f172a', font=("Arial", 9, "bold")).pack(pady=(15, 0))
        self.entry_id = tk.Entry(self.frame, font=("Arial", 18), justify='center', width=20)
        self.entry_id.pack(pady=5, ipady=5)
        
        self.entry_name.focus_set() # Start with cursor on Name

        # Unlock Button
        self.btn = tk.Button(self.frame, text="UNLOCK SYSTEM", command=self.attempt_login, bg='#D4AF37', fg='white', font=("Arial", 12, "bold"), width=25, height=2, cursor="hand2")
        self.btn.pack(pady=30)

        self.force_on_top()

    def force_on_top(self):
        self.root.lift()
        self.root.attributes("-topmost", True)
        self.root.after(2000, self.force_on_top)

    def attempt_login(self):
        name = self.entry_name.get()
        student_id = self.entry_id.get()
        print(f"DEBUG: Attempting login for {name} ({student_id})")
        if not name or not student_id:
            messagebox.showwarning("Input Required", "Please fill in both Name and ID.")
            return

        payload = {
            "pc_number": PC_NUMBER,
            "student_name": name,
            "student_id": student_id
        }

        try:
            response = requests.post(f"{API_URL}/login", json=payload, timeout=5, verify=False)
            
            if response.status_code == 200:
                messagebox.showinfo("Success", f"Access Granted. Hello, {name}!")
                self.root.withdraw() 
                threading.Thread(target=self.heartbeat_loop, daemon=True).start()
            else:
                messagebox.showerror("Error", "Server rejected the login.")
        except Exception:
            messagebox.showerror("Connection Error", "Cannot reach LabGuard Server.")

    def heartbeat_loop(self):
        while True:
            try:
                response = requests.get(f"{API_URL}/status/{PC_NUMBER}", timeout=5, verify=False)
                if response.status_code == 200 and response.json().get('status') == 'locked':
                    self.lock_ui_again()
                    break
            except:
                pass 
            time.sleep(20) 

    def lock_ui_again(self):
        self.entry_name.delete(0, tk.END)
        self.entry_id.delete(0, tk.END)
        self.root.deiconify() 
        self.root.attributes("-topmost", True)
        self.entry_name.focus_set()

def on_shutdown():
    try:
        requests.post(f"{API_URL}/logout", json={"pc_number": PC_NUMBER}, timeout=2)
    except:
        pass

atexit.register(on_shutdown)

if __name__ == "__main__":
    app_root = tk.Tk()
    client = LabGuardClient(app_root)
    app_root.mainloop()
