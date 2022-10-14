# UntisSync

### General information
Untissync is an EGroupware application that can be used to import timetables and substitution plans from WebUntis<sup id="f1">[1](#f1)</sup> to EGroupware.
* list all or specific substitutions
* import substitutions and timetables automatically
* import teacher timetables into the EGroupware calendar
* add classes and rooms as participants in the EGroupware calendar
* updates EGroupware groups with the teacher of their classes
* automatic or manual linking of teachers, classes and rooms from Untis with users and resources in EGroupware

### Requirements and Preparations in WebUntis
To be able to use UntisSync you need a school-specific web access to WebUntis with specific read acces.
<!--* master data of teachers: "Only view"
* using the scheduling tool: "Only view"-->

Login to WebUntis with administrative access and go to _Administration / Users_ and create a new user `egroupware` in WebUntis with a strong password.
Notice this password.
Select `Main office` for this user as _Personal role_ and _User group_.

<!--Go to _Administration_ / _Rights and roles_ and select the group ```Main office```.\
Make sure that members of the group ```Main office``` have at least read access ("Only view") to master data of teachers and
the scheduling tool. -->

Notice your WebUntis Server-URL (like https://_servername_.webuntis.com). You also need the information of your school mandant name. 
This is an internal short name of your institution. If you do not know this name, you can extract 
this name from the source code of the WebUntis login page (search for _mandantName_) or ask the WebUntis Support.

__For the purpose of testing or developing you can create a playground in WebUntis! Make sure you do not use unnecessary polling on the WebUntis production API!__

###  Configuration of EGroupware
1. Add untissync app to EGroupware docker container 
(see also https://github.com/EGroupware/egroupware/wiki/Installation-using-egroupware-docker-RPM-DEB-package#how-to-install-egroupware-gmbhs-epl-version):
mkdir -p /usr/share/egroupware (if it does not yet exist!)
cd /usr/share/egroupware
git clone EGroupware/untissync 
docker restart egroupware (to restart the docker container)
2. Install UntisSync from the EGroupware setup. Configure run rights for the UntisSync application.
3. Create a new (technical) user `untissync` who will later become the owner of the calendar entries. Login via this user will not be necessary.
This step makes it easier to troubleshoot any errors that may occur due to incorrect synchronization.
4. Open the site configuration of UntisSync and complete your settings:

| name           	| value                                                 	                                                                                                                                                                                                      |
|---------------------	|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| WebUntis Server-URL 	| https://_servername_.webuntis.com/WebUntis/jsonrpc.do 	                                                                                                                                                                                                      |
|                     	| _Replace 'servername' with the WebUntis servername for your institution._                                                                                                                                                                                    |
|                     	| 	                                                                                                                                                                                                                                                            |
| WebUntis school name  | _Enter your school mandant name_                                                                                                                                                                                                                             |
|                     	| 	                                                                                                                                                                                                                                                            |
| WebUntis username     | `egroupware`                                                                                                                                                                                                                                                 |
|                       | _Enter the username you created in WebUntis._                                                                                                                                                                                                                |
| WebUntis password     | _Enter the password for this user._                                                                                                                                                                                                                          |
|                       |                                                                                                                                                                                                                                                              |
| Owner of calendar events | `untissync`                                                                                                                                                                                                                                                  |
|                       | _This user becomes the owner of the events._                                                                                                                                                                                                                 |
|                       |                                                                                                                                                                                                                                                              |
| number of days for which the timetable should be loaded in advance | _Enter the number of days for which the data should be imported from WebUntis (default: 14)_                                                                                                                                                                 |
|                       |                                                                                                                                                                                                                                                              |
| integrate the timetable in the calendar | _select the checkbox if the timetables should be imported into the EGroupware calendar_                                                                                                                                                                      |
|                       |                                                                                                                                                                                                                                                              |
| remove expired calendar entries | _If the checkbox is selected, expired events in the EGroupware calender will be removed._                                                                                                                                                                    |
|                       |                                                                                                                                                                                                                                                              |
| prefix of the EGroupware user, representing a class (default: Klasse_) | _For example a class in Untis is named like '5A' which belongs to a (technical) user in EGroupware with the Login-ID 'Klasse_5A'. The given prefix enables the automatic linking of the classes from WebUntis with the corresponding users from EGroupware._ |
|                       |                                                                                                                                                                                                                                                              |  
| Update EGroupware group of teaching teachers per class after import | _Activate the checkbox if the teacher group of the classes should also be updated after each complete import of the timetable._                                                                                                                              |
|                       |                                                                                                                                                                                                                                                              |
| Prefix of the EGroupware group of teaching teachers per class (default: Lehrer_)  | _The given prefix enables the automatic linking of the classes from WebUntis with his teacher group from EGroupware._                                                                                                                                        |
|                       |                                                                                                                                                                                                                                                              |
| Load rooms and classes from Webuntis before importing  | _Activate the checkbox if you want the classes and rooms to be updated by WebUntis before each synchronization._                                                                                                                                             |

### Initial data import
This step is necessary if calendar entries are to be updated in EGroupware. From the sidebar in UntisSync you can open the mapping tools for teachers, classes and rooms.

For each section the import can be initiated manually by klicking the button 'import'. UntisSync tries to automatically assign the corresponding teachers, classes or rooms (ressources) from EGroupware with the imported data.
In some cases the link cannot be made automatically. These cases can then be assigned manually using the context menu. Manual links are retained when importing again.
The rooms and classes are assigned using the value of their name attributes. 

This step should be carried out whenever there have been changes in the teaching staff or the institution has received new classes or rooms.

### Run UntisSync in the background
UntisSync offers two asynchronous jobs to import data regularly and unattended from WebUntis.
To activate these services, select 'Admin-Tools' as admin from the sidebar.
The services can be created via 'Create Async Job Timetable' and 'Create Async Job Substitution'. Delete them by klicking 'Delete Asnyc Jobs'.

After activating these jobs you can check them under _Admin / Asynchrounus timed services_. You should see two services with the names 'untissync_update_timetable' and 'untissync_update_substitutions'.
If both services have been activated, the complete timetables are then re-imported once a night. Every 20 minutes UntisSync checks on the WebUntis server whether new data has been available since the last import of the substitution plan.
If this is the case, the substitution plan is imported.

Both import jobs can also be initiated manually using Admin-Tools for a one-time run. Importing the complete timetables with updating the calendar can take several minutes!

__Make sure you do not use unnecessary polling on the WebUntis production API!__

###  How UntisSync works
After each import of the substitution plan, UntisSync uses the previous data to check which changes have been made.
For every teacher who is affected by changes, the complete timetable is updated.

### Limitation
Timetable entries not assigned to any teacher are currently not loaded from WebUntis.

### Footnotes
<b id="f1">1</b> Untis and WebUntis are product names of the  Untis GMBH (https://www.untis.at).
