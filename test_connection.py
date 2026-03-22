import requests
import urllib3
urllib3.disable_warnings()

url = "https://labguard.test/api/pc/status/PC-01"

try:
    print(f"Connecting to {url}...")
    # We add a 5 second timeout to avoid hanging
    r = requests.get(url, verify=False, timeout=5)
    print(f"Success! Status Code: {r.status_code}")
    print(f"Server Response: {r.text}")
except requests.exceptions.ConnectionError:
    print("FAILED: Connection Refused. (Is Herd running?)")
except requests.exceptions.Timeout:
    print("FAILED: Connection Timed Out. (Check your Firewall)")
except Exception as e:
    print(f"FAILED: Unknown error: {e}")