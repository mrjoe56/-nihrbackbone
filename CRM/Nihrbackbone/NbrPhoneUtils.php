<?php

// Functions commonly used when validating or setting phone numbers -Aly
class CRM_Nihrbackbone_NbrPhoneUtils {


    // Sets the phone type of a phone number based on what it's first 2 characters are (If its UK number)
    public static function selectPhoneType($phoneNumber){

        $phoneType="Phone";
        $firstChars = substr($phoneNumber, 0, 2);
        // If a UK number, assign type
        if($firstChars!=="00"){
        $phoneType = ($firstChars=="07") ? "Mobile": "Phone";
    }
        return $phoneType;
}

    // Checks to see if phone number is correct format, length etc
    public static function validatePhone($phoneNumber)
    {
        $ukPhoneRegex = "/^(?:(?:\(?(?:0(?:0|11)\)?[\s-]?\(?|\+)44\)?[\s-]?(?:\(?0\)?[\s-]?)?)|(?:\(?0))".
            "(?:(?:\d{5}\)?[\s-]?\d{4,5})|(?:\d{4}\)?[\s-]?(?:\d{5}|\d{3}[\s-]?\d{3}))|(?:\d{3}\)?[\s-]?\d{3}[\s-]?\d{3,4})".
            "|(?:\d{2}\)?[\s-]?\d{4}[\s-]?\d{4}))(?:[\s-]?(?:x|ext\.?|\#)\d{3,4})?$/";
        $phoneNumber= \CRM_Nihrbackbone_NbrPhoneUtils::formatPhone($phoneNumber);
        $firstChars = substr($phoneNumber, 0, 2);
        //         validate number (UK only)
        if ($firstChars != "00") {
            //   for mobile check length 11
            if ($firstChars == "07" && strlen($phoneNumber) != 11) {
                return "Mobile phone number needs 11 characters";
            } //   for landline check length 10 or 11
            else if (strlen($phoneNumber) != 10 && strlen($phoneNumber) != 11) {
                return "Landline phone number should be 10 or 11 characters";
            }

            //   check valid service type
            $nbr_codes = ['01', '02', '03', '07'];
            if ( !in_array($firstChars,$nbr_codes)) {
                return "Phone number should start with 01,02,03 or 07";

            }
            //   if valid number
            if (preg_match($ukPhoneRegex,$phoneNumber)) {
                return null;
            } else {
                return "Phone number is invalid";
            }
            return NULL;
        }
        else{
            return NULL;
        }
    }

    // Removes spaces , replaces + with 00 etc for consistency
    public static function formatPhone($phoneNumber){
        $tempPhoneNo = $phoneNumber;
        // remove spaces
        $tempPhoneNo = str_replace(" ", "",$tempPhoneNo);
        // convert + to 00
        $tempPhoneNo = str_replace( "+", "00",$tempPhoneNo);
        // convert 00440 to 0
        $tempPhoneNo = str_replace( "00440", "0",$tempPhoneNo);
        // convert 0044 to 0
        $tempPhoneNo = str_replace( "0044", "0",$tempPhoneNo);
        // remove non-numeric  Do we want to remove non numeric?
        $tempPhoneNo = str_replace( "/\D/g", '',$tempPhoneNo);
        return $tempPhoneNo;
    }

}

