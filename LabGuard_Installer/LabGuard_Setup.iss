; =====================================================================
; LabGuard Single-Application Workstation Installer (Inno Setup 6.x)
; Computer Laboratory Security & Management System
; =====================================================================

#define MyAppName "LabGuard Client"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Araullo University"
#define MyAppExeName "labguard.exe"
#define TaskName "LabGuardClient"

[Setup]
AppId={{D1A392F4-7C90-48E1-831C-D4AF3701}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\LabGuard\Client
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
OutputBaseFilename=LabGuard_Client_Setup
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern

; Prompts for Windows Administrator privileges during installation
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64

[Files]
; Deploy labguard.exe and any accompanying assets from the build folder
Source: "build\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Registry]
; Disable Task Manager to prevent students from force-closing the app
Root: HKLM; Subkey: "SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System"; ValueType: dword; ValueName: "DisableTaskMgr"; ValueData: 1; Flags: uninsdeletevalue

[Run]
; =====================================================================
; 1. AUTO-START VIA TASK SCHEDULER (Runs elevated without UAC prompt)
; =====================================================================
Filename: "schtasks.exe"; Parameters: "/Create /TN ""{#TaskName}"" /TR ""{app}\{#MyAppExeName}"" /SC ONLOGON /RL HIGHEST /F"; Flags: runhidden

; =====================================================================
; 2. FIREWALL EXEMPTION RULES
; =====================================================================
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall add rule name=""LabGuard Client Inbound"" dir=in action=allow program=""{app}\{#MyAppExeName}"" enable=yes"; Flags: runhidden
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall add rule name=""LabGuard Client Outbound"" dir=out action=allow program=""{app}\{#MyAppExeName}"" enable=yes"; Flags: runhidden

; =====================================================================
; 3. WINDOWS DEFENDER EXCLUSION
; =====================================================================
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""Add-MpPreference -ExclusionPath '{app}'"""; Flags: runhidden

; =====================================================================
; 4. LAUNCH IMMEDIATELY AFTER INSTALLATION FINISHES
; =====================================================================
Filename: "schtasks.exe"; Parameters: "/Run /TN ""{#TaskName}"""; Flags: runhidden

[UninstallRun]
; =====================================================================
; CLEANUP ON UNINSTALLATION
; =====================================================================
; Remove Scheduled Task
Filename: "schtasks.exe"; Parameters: "/Delete /TN ""{#TaskName}"" /F"; Flags: runhidden

; Remove Firewall Rules
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall delete rule name=""LabGuard Client Inbound"""; Flags: runhidden
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall delete rule name=""LabGuard Client Outbound"""; Flags: runhidden

; Remove Defender Exclusion Rule
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""Remove-MpPreference -ExclusionPath '{app}'"""; Flags: runhidden

[Code]
var
  ConfigPage: TInputQueryWizardPage;

// Prompt for Server IP, Lab Name, and PC Identifier
procedure InitializeWizard;
begin
  ConfigPage := CreateInputQueryPage(
    wpSelectDir,
    'LabGuard Station Setup',
    'Configure Workstation & Server Parameters',
    'Enter the central server IP address, lab name, and workstation ID for this terminal:'
  );

  ConfigPage.Add('Server IP / Hostname:', False);
  ConfigPage.Add('Lab Name (e.g., LAB1 or CL1):', False);
  ConfigPage.Add('PC Identifier (e.g., pc-1 or PC-15):', False);

  // Set default values displayed in the setup fields
  ConfigPage.Values[0] := '192.168.1.100';
  ConfigPage.Values[1] := 'LAB1';
  ConfigPage.Values[2] := 'pc-1';
end;

// Validate user input
function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;
  if CurPageID = ConfigPage.ID then
  begin
    if (Trim(ConfigPage.Values[0]) = '') or 
       (Trim(ConfigPage.Values[1]) = '') or 
       (Trim(ConfigPage.Values[2]) = '') then
    begin
      MsgBox('Please fill in all configuration fields before continuing.', mbError, MB_OK);
      Result := False;
    end;
  end;
end;

// Generate config.json automatically inside the install folder
procedure CurStepChanged(CurStep: TSetupStep);
var
  ConfigFile: String;
  JsonContent: String;
begin
  if CurStep = ssPostInstall then
  begin
    ConfigFile := ExpandConstant('{app}\config.json');

    JsonContent := 
      '{' + #13#10 +
      '  "server_url": "http://' + ConfigPage.Values[0] + '",' + #13#10 +
      '  "lab": "' + ConfigPage.Values[1] + '",' + #13#10 +
      '  "pc": "' + ConfigPage.Values[2] + '"' + #13#10 +
      '}';

    SaveStringToFile(ConfigFile, JsonContent, False);
  end;
end;