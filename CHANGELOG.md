## 2.35
* 5118 update distance calculation

## 2.32

* cannot send an invite via PDF to someone who has the status invite pending (see https://www.wrike.com/open.htm?id=918982877)

## 2.31
* add property to config container for follow up activity (required for https://www.wrike.com/open.htm?id=709984945)

## 2.30
* add prevent upload to portal field to study form and option values to container (see https://www.wrike.com/open.htm?id=906177216 and https://www.wrike.com/open.htm?id=923547349)

## 2.29
* fix the counter for case activities so bulk mail activities for other contacts are not taken into account (see https://www.wrike.com/open.htm?id=692748479)

## 2.28
* fix function to check for excl online for volunteers (see https://www.wrike.com/open.htm?id=897604243)

## 2.27
* add field for lay title to study data (see https://www.wrike.com/open.htm?id=905311011)

## 2.26
* fix validation of study type (see https://www.wrike.com/open.htm?id=806555844)
* remove multiple visit from study form and from DB (in upgrader)

## 2.25
* fix checks for commercial and online to not treat NULL as a yes value (see https://www.wrike.com/open.htm?id=729447726)

## 2.24
* add function isDataOnly to NbrStudy, which is needed for the generation of DAA study number

## 2.23
* fix issue with invitation pending status on manage case screen (see https://www.wrike.com/open.htm?id=753781734)

## 2.22
* fix error recall group and invite date not merged (see https://www.wrike.com/open.htm?id=868104357)

## 2.21
* fix error in container with bioresourceIdIdentifierType (found when dealing with issue 9126)

## 2.20
* rearrange fields (study type) on study form (see https://www.wrike.com/open.htm?id=806555844)

## 2.19
* fix update of study status invite in manage case screen (see https://www.wrike.com/open.htm?id=753781734)

## 2.18
* when merging, force checked boxes and add new for address/email/phone (see https://www.wrike.com/open.htm?id=827910828)
    * use buildForm hook to set all the relevant checkboxes and default values
    * hide relevant location elements in template with jQuery
    * save original address(es) of main contact in $_SESSION
    * once merge is complete reinstate original address(es) of main contact retrieved from $_SESSION

## 2.17
* when merging contacts remove duplicate panel data (see https://www.wrike.com/open.htm?id=827910828)

## 2.16
* when merging contacts remove unnecessary contact identifiers (see https://www.wrike.com/open.htm?id=827910828)

## 2.15
* when merging contacts, keep all activities of all recruitment cases on the oldest as active case (see https://www.wrike.com/open.htm?id=692748431). Note that for this change some functions from NbrVolunteerCase where moved to the new class NbrRecruitmentCase.

## 2.14
* alphabetize template list for invite by email (see https://www.wrike.com/open.htm?id=837076881)

## 2.13
* study type can not be empty (see https://www.wrike.com/open.htm?id=806555844)

## 2.12
* make sure participant case data (study participant id, eligible status) stays intact when contacts are merged (see https://www.wrike.com/open.htm?id=692748431)

## 2.11
* ensure all volunteers can be imported as long as participant ID exists (see https://www.wrike.com/open.htm?id=706383875)

## Version 2.10
* ensure study types are exclusive (see https://www.wrike.com/open.htm?id=806555844)

## Version 2.9
* set definition public in service container (required for Drupal 9, no functional change)
* add API NbrStudy generateids to generate study participant IDs for data only studies (see https://www.wrike.com/open.htm?id=706388658)

## Version 2.8
* api to remove selected participation cases from study not progressed (see https://www.wrike.com/open.htm?id=753789895)

## Version 2.7
* add new methods for new face to face recall only custom field, excl online eligibility status,  method to check if volunteer is face to face recall only and change eligibility calculation to reflect this (https://www.wrike.com/open.htm?id=728028509)

## Version 2.6
* ensure no activity can be recorded on case or participation data changed when study status does not allow it

## Version 2.5
* add container property for campaign status and add method to check if study has no action status to NbrStudy

## Version 2.4
* wrike issue https://www.wrike.com/open.htm?id=712317179: remove methods to service for guardian contact sub type and guardian relationship type (moved to nbrguardian extension)

## Version 2.3
* wrike issue https://www.wrike.com/open.htm?id=712317179: add methods to service for guardian contact sub type and guardian relationship type

## Version 2.2
* issue 7827: ensure that change case to another client is re-enabled + resurrect participation data on activity create hook for activity of type Reassigned Case

## Version 2.1
* issue 8055: update methods to calculate willing to give blood and able to travel

## Version 2.0
* issue 7983: retrieve study status for which eligibility has to be calculated from settings

## Version 1.89
* issue 7865: fix test error with swapping values

## Version 1.88
* issue 6474: only show eligibility if study status selected

## Version 1.87
* issue 7865: change blood/commercial/travel fields from able to willing

## Version 1.86
* issue 6563: add container methods for disease/medication option groups and custom group + fields

## Version 1.85
* issue 7822: fix subject and start date in NbrVolunteerCase create API

## Version 1.84
* issue 7708: fixed query to find cases where bulk mail activities need to be added to after bulk mailing from MSP

## Version 1.83
* issue 7742: separate settings for max invites and on other study

## Version 1.82
* issue 7508: remove the loaddemographics from nihrbackbone

## Version 1.81
* issue 7564: introduced set_time_limit = 0 when importing participants into study
* issue 7675: use setting for statuses to be considered as invited in calculate eligibility

## Version 1.80
* STRIDES data upload: added blood donor ID

## Version 1.79
* issue 7570: recalculate eligibility if study participation status of volunteer changes

## Version 1.78
* upload multiple files (pid data, contacts) per project

## Version 1.77
* updated phone data import; added NAFLD data

## Version 1.76
* IBD data load, do not update data of volunteers with status other than active or pending

## Version 1.75
* included STRIDES; upload only if existing records are pending or active

## Version 1.74
* IBD daily upload, removed migration code

## Version 1.73
* issue 7462: preg_replace for weird characters that are not empty (see https://stackoverflow.com/questions/45855783/php-how-to-get-rid-of-strange-characters-like-u00a0)

## Version 1.72
* issue 7165: add method to find contact id with email

## Version 1.71
* issue 7165: add method to get contact id with study participant id

## Version 1.70
* issue 7165: add method to check if volunteer is withdrawn
* issue 7460: add parameter mode to caleligibility job so it can also recalculate for status invited/invitation pending/accepted
