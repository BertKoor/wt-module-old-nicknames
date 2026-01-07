# webtrees module for old-style handling of nicknames
Since v2.0.0 of [webtrees](https://www.webtrees.net) the handling of nicknames has changed.
Prior to v2.0.0, in the full displayed name it would insert a nickname in quotation marks between the given names and surname. 

The raw GEDCOM as produced by the old branch of webtrees:
````
1 NAME Martin /White/
2 GIVN Martin
2 SURN White
2 NICK Chalky
````
would be displayed as:

| Name        | Martin "Chalky" White |
|-------------|-----------------------|
| Given names | Martin                |
| Surname     | White                 |
| Nickname    | Chalky                |                

Since v2.0.0 the algorithm was simplified, and this name would be displayed simply as **_Martin White_**.
It was made very clear the `"feature"` was not coming back.
A short explanation according to the [FAQ](https://webtrees.net/faq/nicknames/) :
> The logic fails for many languages and is not always appropriate.

Both GEDCOM and webtrees _do_ facilitate a nickname as part of the full name, but it has to be put in explicitly.
Raw GEDCOM example of that:
````
1 NAME Martin "Chalky" /White/
2 GIVN Martin
2 SURN White
2 NICK Chalky
````

This module provides two things:

1. For editors the name editing form is enhanced to support entry and update of a nickname in this style.
2. For administrators there's a datafix, which puts the nickname in the full name, 
   within quotation marks in between the given names and surname.

## Editing of nicknames
Whereas webtrees 1.7 showed an input field for a nickname by default,
it was by default hidden in webtrees 2.x.
Only when clicking the option __Edit with all GEDCOM tags__ at the bottom of the form,
then hidden fields will be shown, including the Nickname input field.

On the __Control panel__ under __GEDCOM tags__ you can untick the box nex to `NAME:NICK`.
Press __Save__ at the bottom, and all name edit forms will have the Nickname input fields again.

This is standard webtrees functionality, no external module is needed.

### Name editing enhancement
This module brings back the old Nickname editing functionality:

* When a nickname is entered, then it will be `"quoted"` in the full name before the `/surname/`.
* When an existing nickname is edited and the full name contains it within quotation marks before the `/surname/`
  (the default position) then it will be updated there as well.
* The nickname may be moved freely within the full name to whatever position is more appropriate.
  Click on the pencil icon next to the full display name to edit its content.
  After such editing it will not be kept in sync when name parts are edited.

As a preparation the existing nicknames need to be put `"quoted"` in the full name,
for which you need to run:

## Datafix `Old Nicknames`
### To run it:
* Go to your webtrees __Control Panel__ and select your tree
* Under __Family tree__ click on __Data fixes__
* From the drop-down list select data fix __Old Nicknames__ and click __Next__
* As with all datafixes, there are two options:
  * __Search__ will present a list of all records potentially eligible for update.
    The update on each record can individually be previewed and/or executed.
  * __Update all__ will apply the fix to all eligible records.

If your user account is _not_ configured to __Automatically accept changes made by this user__
then all updates will be added to the list of pending changes.
To change that, go to the __Control panel__ section __User administration__.
Click on the __Edit__ icon for your user and tick the box nex to __Changes__.
For good effect this needs to be done _before_ executing the datafix.

### How it works
The datafix will do a coarse selection of records eligible for update, 
which is the list presented after clicking the __Search__ button:
* Individuals with the GEDCOM tag `2 NICK`
* and a `1 NAME` tag which contains at least two slashes
* and the `1 NAME` tag does not contain two quotation marks prior to the first slash
* The first slash should preceded by some text (likely the given name) and a space

This is performed as a SQL query on the database, which means that subtle GEDCOM details cannot be catered for.
For example, when an individual has multiple `NAME` facts, then the SQL query cannot tell them apart.
- If one name fact contains `2 NICK` but another name contains quotation marks, then alas this record is not selected for update.
- If one name fact contains `2 NICK` under a `1 NAME` without any slashes, but another `NAME` does have slashes,
  then the record will be selected for update, but no change will be done.

When update is attempted per individual record, a fine selection is done:
* There should be at least one `NAME` fact
* which contains a `NICK` subtag
* and the `1 NAME` value does contain a pair of slashes preceded by a space
* and the `1 NAME` value does not yet contain the `NICK` value within quotation marks

When these conditions are met, the `NICK` name is put within quotation marks before the first slash within its parent `NAME` value.

In short, given GEDCOM snippet:
````
1 NAME Martin /White/
2 NICK Chalky
````
will be altered to:
````
1 NAME Martin "Chalky" /White/
2 NICK Chalky
````

## Compatibility

### webtrees 2.2
This module is primarily developed for, and tested with webtrees 2.2.4 running under PHP 8.4.

### webtrees 2.1
This module is tested with webtrees 2.1.25 under PHP 7.4 and 8.2, and it seems to work.

### webtrees 2.0
It is generally discouraged to run this version of webtrees and PHP7 it is based on.
_You really should upgrade._ Nevertheless, I have tested this out of curiosity.
The datafix seems to work, but enhanced editing does not work.

### webtrees 1.7
Now seriously, if you are still on webtrees 1.7 then you do not need this module.

### combined with Vesta Classic Look & Feel
This module is tested in combination with the Vesta Classic Look & Feel module.
No conflicts were found, both modules still worked as designed.

If displaying the nickname was the only reason you used Vesta Classic Look & Feel,
then you can uninstall it.

## Installation instructions
On your server there is a directory `modules_v4`.
Create a subdirectory `wt-module-old-nicknames` in there.

Download the source files of this repository as a zip, and unzip them onto your server into the directory you just created.

The end result looks like this:

 * `modules_v4 <dir>` (already exists on your server)
   * `wt-module-old-nicknames <dir>`
     * `resources <dir>`
       * `views <dir>`
         * `edit <dir>`
           * `input-addon-edit-name.phtml`
     * `module.php`
     * `OldNicknames.php`

The files `latest-version.txt` and this `README.md` are not required to be uploaded to your server, but won't do any harm.

## Privacy, telemetry, tracking, etc.
Privacy: yes. Tracking: no. 

There is no way for me to find out how many sites have this module installed, let alone which ones.
It would be simple for me to implement it for the sake of monitoring, but I have chosen not to.

The module will do a check on the latest available version whenever the webtrees Control Panel is opened.
It checks a url on github.com, not on my own server, so traffic data is inaccessible to me.

## License
````
Copyright (C) 2025 BertKoor.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
````
## Warranty
````
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details:
<https://www.gnu.org/licenses/>
````