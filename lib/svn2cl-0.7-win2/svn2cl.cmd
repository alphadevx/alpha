@echo off
rem ************************************************
rem This is a sample batch file.
rem ************************************************

SET REPOSITORY="svn://mysvn/myproject/trunk"

PATH %PATH%;C:\Program Files\Subversion\bin

ECHO Creating ChangeLog.txt from %REPOSITORY% ...

CScript.exe //nologo svn2cl.vbs --group-by-day --linelen 100  %REPOSITORY%

IF ERRORLEVEL 1 PAUSE
