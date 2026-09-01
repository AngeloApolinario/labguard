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

PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64

[Files]
Source: "build\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Registry]
; Disable Task Manager to prevent force-closing the terminal lock
Root: HKLM; Subkey: "SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System"; ValueType: dword; ValueName: "DisableTaskMgr"; ValueData: 1; Flags: uninsdeletevalue

[Run]
; =====================================================================
; 1. FIREWALL EXEMPTION RULES
; =====================================================================
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall add rule name=""LabGuard Client Inbound"" dir=in action=allow program=""{app}\{#MyAppExeName}"" enable=yes"; Flags: runhidden
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall add rule name=""LabGuard Client Outbound"" dir=out action=allow program=""{app}\{#MyAppExeName}"" enable=yes"; Flags: runhidden

; =====================================================================
; 2. WINDOWS DEFENDER EXCLUSION
; =====================================================================
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""Add-MpPreference -ExclusionPath '{app}'"""; Flags: runhidden

; =====================================================================
; 3. LAUNCH IMMEDIATELY AFTER INSTALLATION FINISHES
; =====================================================================
Filename: "schtasks.exe"; Parameters: "/Run /TN ""{#TaskName}"""; Flags: runhidden

[UninstallRun]
; Cleanup Scheduled Task, Firewall rules, and Defender exclusion
Filename: "schtasks.exe"; Parameters: "/Delete /TN ""{#TaskName}"" /F"; Flags: runhidden
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall delete rule name=""LabGuard Client Inbound"""; Flags: runhidden
Filename: "{sys}\netsh.exe"; Parameters: "advfirewall firewall delete rule name=""LabGuard Client Outbound"""; Flags: runhidden
Filename: "powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""Remove-MpPreference -ExclusionPath '{app}'"""; Flags: runhidden

[Code]
var
  ConfigPage: TInputQueryWizardPage;

procedure InitializeWizard;
begin
  ConfigPage := CreateInputQueryPage(
    wpSelectDir,
    'LabGuard Station Setup',
    'Configure Workstation & Server Parameters',
    'Enter the central server URL, lab name, and workstation ID for this terminal:'
  );

  ConfigPage.Add('Server URL / Hostname:', False);
  ConfigPage.Add('Lab Name (e.g., LAB1 or CL1):', False);
  ConfigPage.Add('PC Identifier (e.g., pc-1 or PC-15):', False);

  // Default values
  ConfigPage.Values[0] := 'https://labguard.it.com';
  ConfigPage.Values[1] := 'LAB1';
  ConfigPage.Values[2] := 'pc-1';
end;

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

procedure RegisterScheduledTask;
var
  ScriptPath: String;
  PSContent: String;
  ResultCode: Integer;
begin
  ScriptPath := ExpandConstant('{tmp}\register_task.ps1');

  // Registers task using Group 'BUILTIN\Users' for any active desktop logon
  PSContent := 
    '$action = New-ScheduledTaskAction -Execute "' + ExpandConstant('{app}\{#MyAppExeName}') + '" -WorkingDirectory "' + ExpandConstant('{app}') + '"' + #13#10 +
    '$trigger = New-ScheduledTaskTrigger -AtLogOn' + #13#10 +
    '$principal = New-ScheduledTaskPrincipal -GroupId "BUILTIN\Users" -RunLevel Highest' + #13#10 +
    'Register-ScheduledTask -TaskName "{#TaskName}" -Action $action -Trigger $trigger -Principal $principal -Force' + #13#10;

  SaveStringToFile(ScriptPath, PSContent, False);

  // Execute PowerShell script silently
  Exec('powershell.exe', '-NoProfile -ExecutionPolicy Bypass -File "' + ScriptPath + '"', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  
  // Clean up
  DeleteFile(ScriptPath);
end;

procedure CurStepChanged(CurStep: TSetupStep);
var
  ConfigFile: String;
  JsonContent: String;
  ServerUrl: String;
begin
  if CurStep = ssPostInstall then
  begin
    // 1. Generate config.json with auto-formatted URL
    ConfigFile := ExpandConstant('{app}\config.json');
    ServerUrl := Trim(ConfigPage.Values[0]);

    // Prepend https:// if no protocol is specified
    if (Pos('http://', LowerCase(ServerUrl)) = 0) and (Pos('https://', LowerCase(ServerUrl)) = 0) then
      ServerUrl := 'https://' + ServerUrl;

    // Remove any trailing slashes
    while (Length(ServerUrl) > 0) and (Copy(ServerUrl, Length(ServerUrl), 1) = '/') do
      Delete(ServerUrl, Length(ServerUrl), 1);

    // Append /api/pc if not already present
    if Pos('/api/pc', LowerCase(ServerUrl)) = 0 then
      ServerUrl := ServerUrl + '/api/pc';

    JsonContent := 
      '{' + #13#10 +
      '  "server_url": "' + ServerUrl + '",' + #13#10 +
      '  "lab": "' + Trim(ConfigPage.Values[1]) + '",' + #13#10 +
      '  "pc": "' + Trim(ConfigPage.Values[2]) + '"' + #13#10 +
      '}';

    SaveStringToFile(ConfigFile, JsonContent, False);

    // 2. Register Scheduled Task
    RegisterScheduledTask;
  end;
end;