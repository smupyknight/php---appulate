<?php
    
    class Acord125Parser {

        public static function parse($data) {
        
            $servername = "twiliodb.cayi02dboyhq.us-west-1.rds.amazonaws.com";
            $username = "appulate";
            $password = "qZEx99bueQ6QL7Td";
            $dbname = "appulate";

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);

            if (mysqli_connect_error()) {
                die("Database connection failed: " . mysqli_connect_error());
            }

            $rq_uid             =   $data->RqUID;

        // ---------------------------------- Transaction table ------------------------------------------------ //

            $request_dt         =   $data->TransactionRequestDt;
            $effective_dt       =   $data->TransactionEffectiveDt;
            $cur_cd             =   $data->CurCd;
            $version_cd         =   $data->ACORDStandardVersionCd;
            $signed_dt          =   $data->CommlPolicy->SignedDt;

            $sql = 'INSERT INTO transaction (rq_uid, request_dt, effective_dt, cur_cd, version_cd, signed_dt, created_at, updated_at)
                    VALUES ("'
                        . $rq_uid . '", "'
                        . $request_dt . '", "'
                        . $effective_dt . '", "'
                        . $cur_cd . '", "'
                        . $version_cd . '", "'
                        . $signed_dt . '", "'
                        . date("Y-m-d h:i:sa") . '", "'
                        . date("Y-m-d h:i:sa") . '")';

            $conn->query($sql);

        // ---------------------------------- Identification table ------------------------------------------------ //

            $generalPartyInfo = $data->Producer->GeneralPartyInfo;
            $commlPolicy = $data->CommlPolicy;

            $agency_name = $generalPartyInfo->NameInfo->CommlName->CommercialName;
            $street = $generalPartyInfo->Addr->Addr1;
            $city = $generalPartyInfo->Addr->City;
            $state = $generalPartyInfo->Addr->StateProvCd;
            $zipcode = $generalPartyInfo->Addr->PostalCode;

            $first_name = $last_name = '';
            foreach ($generalPartyInfo->NameInfo as $nameInfo) {
                if ($nameInfo['id'] == 'ProducerName') {
                    $first_name = $nameInfo->PersonName->GivenName;
                    $last_name = $nameInfo->PersonName->Surname;
                }
            }

            $phoneNumber = $generalPartyInfo->Communications->PhoneInfo;
            $phone = $fax = '';

            foreach ($phoneNumber as $ph) {
                if ($ph->PhoneTypeCd == 'Phone') {
                    $phone = $ph->PhoneNumber;
                }
                else if ($ph->PhoneTypeCd == 'Fax') {
                    $fax = $ph->PhoneNumber;
                }
            }

            $email = $generalPartyInfo->Communications->EmailInfo->EmailAddr;                       
            $code = $data->Producer->ProducerInfo->ContractNumber;
            $subcode = $data->Producer->ProducerInfo->ProducerSubCode;
            $agency_customer_id = $data->InsuredOrPrincipal->ItemIdInfo->AgencyId;
            
            $carrier = '';

            foreach ($data->RemarkText as $text) {
                if ($text['IdRef'] == 'Producer') {
                    $carrier = $text;
                }
            }

            $naic_code = $commlPolicy->NAICCd;
            $comp_prog_name = '';

            foreach ($data->RemarkText as $text) {
                if ($text['IdRef'] == 'CompanyProduct') {
                    $comp_prog_name = $text;
                }
            }

            $program_code = $commlPolicy->CompanyProductCd;
            $policy_number = $commlPolicy->PolicyNumber;

            $underwriter = $underwriter_office = '';
            $miscParty = $commlPolicy->MiscParty;
            foreach ($miscParty as $party) {
                if ($party->MiscPartyInfo->MiscPartyRoleCd == 'UN') {
                    $underwriter = $party->GeneralPartyInfo->NameInfo->PersonName->Surname;
                }
            }

            $quote_bound = $commlPolicy->PolicyStatusCd;
            $date = $commlPolicy->Binder->ContractTerm->EffectiveDt;
            $time = $commlPolicy->Binder->ContractTerm->StartTime;

        // ---------------------------------- Sections Attached table ------------------------------------------------ //


            $arvp = $bm = $ba = $bo = $cgl = $cmc = $dealers = $edp = $ef = $gad = $gas = $ibr = $oc = $property = $tmtc = $tmc = $umbrella = $yacht = '';
            foreach ($data->RemarkText as $remarkText) {
                if ($remarkText['IdRef'] == 'PolicyLevel') {
                    if (substr($remarkText, 0, 36) == 'Accounts Receivable/ Valuable Papers') {
                        $arvp = substr($remarkText, 39);
                    }
                    if (substr($remarkText, 0, 7) == 'Dealers') {
                        $dealers = substr($remarkText, 10);
                    }

                    if (substr($remarkText, 0, 17) == 'Equipment Floater') {
                        $ef = substr($remarkText, 20);
                    }

                    if (substr($remarkText, 0, 14) == 'Glass And Sign') {
                        $gas = substr($remarkText, 17);
                    }

                    if (substr($remarkText, 0, 26) == 'Installation/Builders Risk') {
                        $ibr = substr($remarkText, 29);
                    }

                    if (substr($remarkText, 0, 10) == 'Open Cargo') {
                        $oc = substr($remarkText, 13);
                    }

                    if (substr($remarkText, 0, 33) == 'Transportation/ Motor Truck Cargo') {
                        $tmtc = substr($remarkText, 36);
                    }

                    if (substr($remarkText, 0, 22) == 'Truckers/Motor Carrier') {
                        $tmc = substr($remarkText, 25);
                    }

                    if (substr($remarkText, 0, 5) == 'Yacht') {
                        $yacht = substr($remarkText, 8);
                    }
                }
            }

            $bm = $data->BoilerMachineryLineBusiness->CurrentTermAmt->Amt;
            $ba = $data->CommlAutoLineBusiness->CurrentTermAmt->Amt;
            $bo = $data->BOPLineBusiness->CurrentTermAmt->Amt;
            $cgl = $data->GeneralLiabilityLineBusiness->CurrentTermAmt->Amt;
            $cmc = $data->CrimeLineBusiness->CurrentTermAmt->Amt;
            $edp = $data->EDPLineBusiness->CurrentTermAmt->Amt;
            $gad = $data->CommlAutoLineBusiness->CurrentTermAmt->Amt;
            $umbrella = $data->CommlUmbrellaLineBusiness->CurrentTermAmt->Amt;

        // ---------------------------------- Policy Information table ------------------------------------------------ //

            $prop_eff_date = $commlPolicy->ContractTerm->EffectiveDt;
            $prop_exp_date = $commlPolicy->ContractTerm->ExpirationDt;
            $billing_plan = $commlPolicy->BillingMethodCd;
            $payment_plan = $commlPolicy->PaymentOption->PaymentPlanCd;
            $method_of_payment = $commlPolicy->PaymentOption->MethodPaymentCd;
            $audit = $commlPolicy->CommlPolicySupplement->AuditFrequencyCd;
            $deposit = $commlPolicy->PaymentOption->DepositAmt->Amt;
            $minimum_premium = $commlPolicy->MinPremAmt->Amt;
            $policy_premium = $commlPolicy->CurrentTermAmt->Amt;

        // ---------------------------------- Applicant Information table ------------------------------------------------ //

            $insOrPrin = $data->InsuredOrPrincipal;

            foreach ($data->InsuredOrPrincipal as $principal) {
                if ($principal->InsuredOrPrincipalInfo->InsuredOrPrincipalRoleCd == 'FNI' || $principal->InsuredOrPrincipalInfo->InsuredOrPrincipalRoleCd == 'AI') {
                    if ( isset($principal->GeneralPartyInfo->Communications) ) {
                        $generalPartyInfo = $data->InsuredOrPrincipal->GeneralPartyInfo;
                        $insOrPrinInfo = $data->InsuredOrPrincipal->InsuredOrPrincipalInfo;

                        $name = $generalPartyInfo->NameInfo->CommlName->CommercialName;
                        $street = $generalPartyInfo->Addr->Addr1;
                        $city = $generalPartyInfo->Addr->City;
                        $state_prov_cd = $generalPartyInfo->Addr->StateProvCd;
                        $postal_code = $generalPartyInfo->Addr->PostalCode;
                        $gl_code = $insOrPrinInfo->BusinessInfo->GeneralLiabilityCd;
                        $sic = $insOrPrinInfo->BusinessInfo->SICCd;
                        $naics = $insOrPrinInfo->BusinessInfo->NAICSCd;
                        $fein_or_soc_sec = $generalPartyInfo->NameInfo->TaxIdentity->TaxId;
                        $business_phone = $generalPartyInfo->Communications->PhoneInfo->PhoneNumber;
                        $website_address = $generalPartyInfo->Communications->WebsiteInfo->WebsiteURL;
                        $legal_entity = $generalPartyInfo->NameInfo->LegalEntityCd;
                        $num_mem_man = $insOrPrinInfo->BusinessInfo->NumMembersManagers;

                        // echo $name . "-----" . $street . "-----" . $city . "-----" . $state_prov_cd . "-----" . $postal_code . "-----" . $gl_code . "-----" . $sic . "-----" . $naics 
                        //  . "-----" . $fein_or_soc_sec. "-----" . $business_phone. "-----" . $website_address. "-----" . $legal_entity. "-----" . $num_mem_man;
                    }
                }
            }

        // ---------------------------------- Contact Information table ------------------------------------------------ //

            foreach ($miscParty as $party) {
                $personName = $party->GeneralPartyInfo->NameInfo->PersonName;
                $communications = $party->GeneralPartyInfo->Communications;

                $contact_type = $party->MiscPartyInfo->MiscPartyRoleCd;

                $first_name = $personName->GivenName;
                $middle_name = $personName->OtherGivenName;
                $surname = $personName->Surname;
                $title_prefix = $personName->TitlePrefix;
                $name_suffix = $personName->NameSuffix;

                $primary_phone = $communications->PhoneInfo[0]->PhoneNumber;
                $primary_phone_type = '';
                if ( $communications->PhoneInfo[0]->PhoneTypeCd == 'Cell' ) {
                    $primary_phone_type = $communications->PhoneInfo[0]->PhoneTypeCd;
                }
                else {
                    $primary_phone_type = $communications->PhoneInfo[0]->CommunicationUseCd;
                }

                $secondary_phone = $communications->PhoneInfo[1]->PhoneNumber;
                $secondary_phone_type = '';
                if ( $communications->PhoneInfo[1]->PhoneTypeCd == 'Cell' ) {
                    $secondary_phone_type = $communications->PhoneInfo[1]->PhoneTypeCd;
                }
                else {
                    $secondary_phone_type = $communications->PhoneInfo[1]->CommunicationUseCd;
                }

                $primary_email = $secondary_email = '';
                foreach ($communications->EmailInfo as $email) {
                    if ($email->CommunicationUseCd == 'Business') {
                        $primary_email = $email->EmailAddr;
                    }
                    else if ($email->CommunicationUseCd == 'Alternate') {
                        $secondary_email = $email->EmailAddr;
                    }
                }
            }

        // ---------------------------------- Premises Information table ------------------------------------------------ //

            $locations = $data->Location;

            foreach($locations  as $location) {
                $loc_num = $location->ItemIdInfo->AgencyId;
                $bld_num = $location->SubLocation->ItemIdInfo->AgencyId;
                $street = $location->Addr->Addr1;
                $city = $location->Addr->City;
                $county = $location->Addr->County;
                $state = $location->Addr->StateProvCd;
                $zipcode = $location->Addr->PostalCode;
                $city_limits = $location->RiskLocationCd;

                foreach ($data->CommlSubLocation as $loc) {
                    if ($loc['LocationRef'] == 'Location_' . $loc_num) {
                        $interest = $loc->InterestCd;
                        $full_time_empl = $loc->BldgOccupancy->NumEmployeesFullTime;
                        $part_time_empl = $loc->BldgOccupancy->NumEmployeesPartTime;
                        $annual_revenues = $loc->CrimeInfo->AnnualGrossReceiptsAmt->Amt;
                        $occupied_area = $loc->BldgOccupancy->AreaOccupied->NumUnits;
                        $open_to_public = $loc->BldgOccupancy->AreaOpenToPublic->NumUnits;
                        $total_building_area = $loc->Construction->BldgArea->NumUnits;
                        $area_leased = $loc->BldgOccupancy->AreaLeasedInd;
                        $desc_of_oper = $loc->BldgOccupancy->OperationsDesc;
                    }
                }
            }

        // ---------------------------------- Nature of Business table ------------------------------------------------ //

            $nature_business_cd = $commlPolicy->CommlPolicySupplement->NatureBusinessCd;
            $business_start_dt = $commlPolicy->CommlPolicySupplement->BusinessStartDt;
            $desc_of_pri_oper = $commlPolicy->CommlPolicySupplement->OperationsDesc;
            $on_percent = $off_percent = '';

            foreach ($insOrPrin as $prin) {
                if ($prin->InsuredOrPrincipalInfo->InsuredOrPrincipalRoleCd == 'AI') {
                    $desc_other_named_insureds = $prin->InsuredOrPrincipalInfo->BusinessInfo->OperationsDesc;
                    break;
                }
            }

            //echo $nature_business_cd . "-----" . $business_start_dt . "-----" . $desc_of_pri_oper . "-----" . $on_percent . "-----" . $desc_other_named_insureds;

        // ---------------------------------- Additional Interest table ------------------------------------------------ //

            foreach ($data->Location as $loc) {
                $addInterest = $loc->SubLocation->AdditionalInterest;

                $interest = $addInterest->AdditionalInterestInfo->NatureInterestCd;
                $rank = $addInterest->AdditionalInterestInfo->InterestRank;
                $certificate = $addInterest->AdditionalInterestInfo->CertificateFrequencyCd;
                $policy = $addInterest->AdditionalInterestInfo->PolicyFrequencyCd;
                $send_bill = $addInterest->AdditionalInterestInfo->BillFrequencyCd;
                $name = $addInterest->GeneralPartyInfo->NameInfo->CommlName->CommercialName;
                $street = $addInterest->GeneralPartyInfo->Addr->Addr1;
                $city = $addInterest->GeneralPartyInfo->Addr->StateProvCd;
                $postal_code = $addInterest->GeneralPartyInfo->Addr->PostalCode;
                $reference_loal = $addInterest->AdditionalInterestInfo->AccountNumberId;
                $interest_end_dt = $addInterest->AdditionalInterestInfo->InterestEndDt;
                $lien_amount = $addInterest->AdditionalInterestInfo->FinancedAmt->Amt;
                $phone = $addInterest->GeneralPartyInfo->Communications->PhoneInfo->PhoneNumber;
                $interest_in_item_number = '';
                $email_addr = $addInterest->GeneralPartyInfo->Communications->EmailAddr;

                foreach ($data->RemarkText as $text) {
                    if ($text['IdRef'] == 'AdditionalInterest_L1_SL1_V2') {
                        $description = $text;
                    }
                }

                $reason_for_interest = $addInterest->AdditionalInterestInfo->ReasonDesc;
            }

        // ---------------------------------- General Information table ------------------------------------------------ //

            foreach ($commlPolicy->QuestionAnswer as $qa) {

                $question_cd = $qa->QuestionCd;
                $answer = $qa->YesNoCd;
                
                if ($qa->Explanation == 'GENRL47' || $qa->Explanation == 'CGL08') {
                    $safety_manual = $commlPolicy->CommlPolicySupplement->SafetyManualInd;
                    $safety_position = $commlPolicy->CommlPolicySupplement->SafetyPositionInd;
                    $monthly_meetings = $commlPolicy->CommlPolicySupplement->MonthlySafetyMeetingInd;
                    $osha = $commlPolicy->CommlPolicySupplement->OSHASafetyProgramInd;
                    $other = $commlPolicy->CommlPolicySupplement->OtherSafetyProgramInd;
                }
                else {
                    if (isset($qa->QuestionOccurrenceInfo)) {
                        $occurrence_dt = $qa->QuestionOccurrenceInfo->OccurrenceDt;
                        $explanation = $qa->QuestionOccurrenceInfo->Explanation;
                        $resolution = $qa->QuestionOccurrenceInfo->ResolutionDesc;
                        $resolution_dt = $qa->QuestionOccurrenceInfo->ResolutionDt;
                    }
                    else {
                        $explanation = $qa->Explanation;
                    }
                }
            }

        // ---------------------------------- Prior Carrier Information table ------------------------------------------------ //

            $category = $general_liability = $automobile = $property = $other = '';
            foreach ($commlPolicy->OtherOrPriorPolicy as $policy) {
                $year = '';
                $category = 'Carrier';

                if (isset($policy->LOBCd)) {
                    if ($policy->LOBCd == 'CGL') {
                        $general_liability = $policy->InsurerName;
                    }
                    else if ($policy->LOBCd == 'AUTOB') {
                        $automobile = $policy->InsurerName;
                    }
                    else if ($policy->LOBCd == 'PROPC') {
                        $property = $policy->InsurerName;
                    }
                }
                else {
                    $other = $policy->InsurerName;
                }
            }

            foreach ($commlPolicy->OtherOrPriorPolicy as $policy) {
                $year = '';
                $category = 'Policy Number';

                if (isset($policy->LOBCd)) {
                    if ($policy->LOBCd == 'CGL') {
                        $general_liability = $policy->PolicyNumber;
                    }
                    else if ($policy->LOBCd == 'AUTOB') {
                        $automobile = $policy->PolicyNumber;
                    }
                    else if ($policy->LOBCd == 'PROPC') {
                        $property = $policy->PolicyNumber;
                    }
                }
                else {
                    $other = $policy->PolicyNumber;
                }
            }

            foreach ($commlPolicy->OtherOrPriorPolicy as $policy) {
                $year = '';
                $category = 'Premium';

                if (isset($policy->LOBCd)) {
                    if ($policy->LOBCd == 'CGL') {
                        $general_liability = $policy->PolicyAmt->Amt;
                    }
                    else if ($policy->LOBCd == 'AUTOB') {
                        $automobile = $policy->PolicyAmt->Amt;
                    }
                    else if ($policy->LOBCd == 'PROPC') {
                        $property = $policy->PolicyAmt->Amt;
                    }
                }
                else {
                    $other = $policy->PolicyAmt->Amt;
                }
            }

            foreach ($commlPolicy->OtherOrPriorPolicy as $policy) {
                $year = '';
                $category = 'Effective Date';

                if (isset($policy->LOBCd)) {
                    if ($policy->LOBCd == 'CGL') {
                        $general_liability = $policy->ContractTerm->EffectiveDt;
                    }
                    else if ($policy->LOBCd == 'AUTOB') {
                        $automobile = $policy->ContractTerm->EffectiveDt;
                    }
                    else if ($policy->LOBCd == 'PROPC') {
                        $property = $policy->ContractTerm->EffectiveDt;
                    }
                }
                else {
                    $other = $policy->ContractTerm->EffectiveDt;
                }
            }

            foreach ($commlPolicy->OtherOrPriorPolicy as $policy) {
                $year = '';
                $category = 'Expiration Date';

                if (isset($policy->LOBCd)) {
                    if ($policy->LOBCd == 'CGL') {
                        $general_liability = $policy->ContractTerm->ExpirationDt;
                    }
                    else if ($policy->LOBCd == 'AUTOB') {
                        $automobile = $policy->ContractTerm->ExpirationDt;
                    }
                    else if ($policy->LOBCd == 'PROPC') {
                        $property = $policy->ContractTerm->ExpirationDt;
                    }
                }
                else {
                    $other = $policy->ContractTerm->ExpirationDt;
                }
            }

        // ---------------------------------- Loss History table ------------------------------------------------ //

            $for_the_last = $commlPolicy->NumLossesYrs;
            $total_losses = $commlPolicy->NumLosses;

            foreach ($commlPolicy->Loss as $loss) {
                if (isset($loss->LOBCd)) {
                    $date_of_occurrence = $loss->LossDt;
                    $line = $loss->LOBCd;
                    $type_desc = $loss->LossDesc;
                    $date_of_claim = $loss->ReportedDt;
                    $amount_paid = $loss->TotalPaidAmt->Amt;
                    $amount_reserved = $loss->ProbableIncurredAmt->Amt;
                    $subrogatioin = $loss->ClaimStatusCd;
                    $claim_open = $loss->ClaimStatusCd;
                }
            }

        // ---------------------------------- Signature table ------------------------------------------------ //

            foreach ($commlPolicy->Loss as $loss) {
                if (isset($loss->NoticeInformationPracticesInd)) {
                    $copy_of_notice = $loss->NoticeInformationPracticesInd;
                }
            }

            $spln = $data->Producer->ProducerInfo->License->LicensePermitNumber;
            $npn = $data->Producer->ProducerInfo->NIPRId;
        }
    }
?>