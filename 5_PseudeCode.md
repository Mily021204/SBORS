Admin Log in

START

Input Username
Input Password
IF Username and Password are correct THEN
Open Dashboard
ELSE
Display "Invalid Username or Password"
ENDIF

END

Add Ordinance

START

Input Ordinance Code
Input Ordinance Title
Input Date Approved
Input Description
Select Category
Upload PDF

IF all fields are complete THEN
Save to Database
Display "Ordinance Successfully Saved"
ELSE
Display "Please Complete Required Fields"
ENDIF

END

Add Resolution

START

Input Resolution Number
Input Resolution Title
Input Date Approved
Input Description
Select Category
Upload PDF

IF all fields are complete THEN
Save to Database
Display "Resolution Successfully Saved"
ELSE
Display "Please Complete Required Fields"
ENDIF

END

Search Ordinance

START

Input Search Keyword

Search Database

IF record exists THEN
Display Ordinance Information
Display View PDF Button
ELSE
Display "No Record Found"
ENDIF

END

Search Resolution

START

Input Search Keyword

Search Database

IF record exists THEN
Display Resolution Information
Display View PDF Button
ELSE
Display "No Record Found"
ENDIF

END

Delete Record

START

Select Record

Display Confirmation

IF Yes THEN
Delete Record
Display "Successfully Deleted"
ELSE
Cancel Operation
ENDIF

END
