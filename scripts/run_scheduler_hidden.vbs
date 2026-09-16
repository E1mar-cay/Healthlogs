Option Explicit

Dim shell, fileSystem, scriptDirectory, phpPath, command
Set shell = CreateObject("WScript.Shell")
Set fileSystem = CreateObject("Scripting.FileSystemObject")

scriptDirectory = fileSystem.GetParentFolderName(WScript.ScriptFullName)
phpPath = "C:\xampp\php\php.exe"

If Not fileSystem.FileExists(phpPath) Then
    phpPath = "php.exe"
End If

shell.CurrentDirectory = scriptDirectory
command = Chr(34) & phpPath & Chr(34) & " " & Chr(34) & scriptDirectory & "\cron_reminders.php" & Chr(34)
shell.Run command, 0, True