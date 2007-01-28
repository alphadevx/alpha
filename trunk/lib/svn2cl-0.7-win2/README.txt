
This is a VBScript port of svn2cl.


Original svn2cl can be found at  http://ch.tudelft.nl/~arthur/svn2cl/ .



HOW TO USE
==========

1. Edit svn2html.xsl.
   Svn2cl.vbs uses the system-default encoding for writing.
   It causes encoding mismatch in the HTML file because the following line
   is written in the HTML file.

     <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

   To avoid this problem, change "charset" to your system-default encoding like:

     <meta http-equiv="Content-Type" content="text/html; charset=shift_jis" />


2. Add subversion directory to the PATH environment variable for executing
   svn.exe.

     PATH %PATH%;C:\Program Files\Subversion\bin


3. Run svn2cl.vbs with CScript.exe or WScript.exe.
   At least you have to specify a repository path which you want to get logs.

     CScript.exe svn2cl.vbs --group-by-day svn://mysvn/myproject/trunk


To get more options, try "CScript.exe svn2cl.vbs --help" .



CHANGES FROM ORIGINAL SCRIPT
============================

- Use MSXML for processing XSLT.

- Default file name in the text format is ChangeLog.txt.
  Use CRLF at the end of line.



FEEDBACK
========
Please email to Iwasa Kazmi <iwasa@cosmo-system.jp>.

If you have improvements of the XSL files, it would be better to contact
Arthur de Jong <arthur@ch.tudelft.nl>, creator of the original script.

