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
