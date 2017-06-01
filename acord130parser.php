<?php

	class Acord130Parser {

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

			//$data 				= 	$acord->InsuranceSvcRq->WorkCompPolicyQuoteInqRq;
			$rq_uid 			= 	$data->RqUID;

		// ---------------------------------- Transaction table ------------------------------------------------ //

			$request_dt 		= 	$data->TransactionRequestDt;
			$effective_dt 		= 	$data->TransactionEffectiveDt;
			$cur_cd 			= 	$data->CurCd;
			$version_cd 		= 	$data->ACORDStandardVersionCd;
			$signed_dt			=	$data->CommlPolicy->SignedDt;

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

			$genPInfo = $data->Producer->GeneralPartyInfo;

			$agency_name 			= 	$genPInfo->NameInfo[0]->CommlName->CommercialName;
			$street 					= 	$genPInfo->Addr->Addr1;
			$city 					= 	$genPInfo->Addr->City;
			$state_prov_cd 			= 	$genPInfo->Addr->StateProvCd;
			$postal_code 			= 	$genPInfo->Addr->PostalCode;

			foreach ($genPInfo->NameInfo as $ni) {
				if ($ni['id'] == 'ProducerName') {
					$first_name = $ni->PersonName->GivenName;
					$last_name = $ni->PersonName->Surname;
				}
			}

			$representative_name 	= 	'';

			foreach ($data->CommlPolicy->MiscParty as $mp) {
				if ($mp->MiscPartyInfo->MiscPartyRoleCd == 'C') {
					$representative_name =  $mp->GeneralPartyInfo->NameInfo->PersonName;
				}
			}

			$office_phone = $mobile_phone = $fax = '';

			foreach ($genPInfo->Communications->PhoneInfo as $phone) {

				if ($phone->PhoneTypeCd == 'Phone') {
					$office_phone = $phone->PhoneNumber;
				}
				else if ($phone->PhoneTypeCd == 'Cell') {
					$mobile_phone = $phone->PhoneNumber;
				}
				else if ($phone->PhoneTypeCd == 'Fax') {
					$fax = $phone->PhoneNumber;
				}
			}

			$email = $genPInfo->Communications->EmailInfo->EmailAddr;

			$code = $sub_code = '';
			if (isset($genPInfo->Communcations->Code)) {
				$code = $genPInfo->Communications->Code;
			}
			if (isset($genPInfo->Communcations->SubCode)) {
				$sub_code = $genPInfo->Communications->SubCode;
			}

			$customer_id = $data->InsuredOrPrincipal->ItemIdInfo->AgencyId;

			$genPInfo = $data->InsuredOrPrincipal->GeneralPartyInfo;
			$insOrPrinInfo = $data->InsuredOrPrincipal->InsuredOrPrincipalInfo;
			$insOrPrin = $data->InsuredOrPrincipal;

			$company = $underwriter = '';
			if (isset($genPInfo->Company)) {
				$company = $genPInfo->Company;
			}
			if (isset($genPInfo->UnderWriter)) {
				$company = $genPInfo->UnderWriter;
			}

			foreach ($data->RemarkText as $text) {
				if ($text['IdRef'] == 'Producer') {
					$applicant_name = $text;
				}
			}

			$office_phone1 = $mobile_phone1 = '';
			foreach ($genPInfo->Communications->PhoneInfo as $phone) {

				if ($phone->PhoneTypeCd == 'Phone') {
					$office_phone1 = $phone->PhoneNumber;
				}
				else if ($phone->PhoneTypeCd == 'Cell') {
					$mobile_phone1 = $phone->PhoneNumber;
				}

			}

			$mailing_street			= 	$genPInfo->Addr->Addr1;
			$mailing_city			= 	$genPInfo->Addr->City;
			$mailing_state 			= 	$genPInfo->Addr->StateProvCd;
			$mailing_zipcode		= 	$genPInfo->Addr->PostalCode;

			$yrs_in_bus				=	$insOrPrinInfo->PrincipalInfo->LengthTimeInBusiness->NumUnits;
			$sic 					=	$insOrPrinInfo->BusinessInfo->SICCd;
			$naisc 					=	$insOrPrinInfo->BusinessInfo->NAICSCd;
			$website_address		=	$genPInfo->Communications->WebsiteInfo->WebsiteURL;
			$email_address1			=	$genPInfo->Communications->EmailInfo->EmailAddr;
			$legal_entity_cd		=	$genPInfo->NameInfo->LegalEntityCd;

			$credit_bureau_name = $id_number = '';
			foreach ($insOrPrin->ItemIdInfo->OtherIdentifier as $otherIdentifier) {
				if ($otherIdentifier->OtherIdTypeCd == 'CreditBureau') {
					$credit_bureau_name = $otherIdentifier->OtherId;
				}
				else if ($otherIdentifier->OtherIdTypeCd == 'Insured') {
					$id_number = $otherIdentifier->OtherId;
				}
			}

			$federal_employer_id_number = $genPInfo->NameInfo->TaxIdentity->TaxId;

			$ncci_risk_id_number = $other_rating_bureau_id = '';
			foreach ($genPInfo->NameInfo->NonTaxIdentity as $nonTaxIdentity) {
				if ($nonTaxIdentity->NonTaxIdTypeCd == 'StateBureau') {
					$ncci_risk_id_number = $nonTaxIdentity->NonTaxId;
				}
				else if ($nonTaxIdentity->NonTaxIdTypeCd == $credit_bureau_name) {
					$other_rating_bureau_id = $nonTaxIdentity->NonTaxId;
				}
			}

			$sql = 'INSERT INTO t130_identification (rq_uid, agency_name, street, city, state_prov_cd, postal_code, first_name, last_name, respresentative_name, office_phone, 
					mobile_phone, fax, email, code, sub_code, customer_id, company, underwriter, applicant_name, office_phone1, mobile_phone1, mailing_street, mailing_city,
					mailing_state, mailing_zipcode, yrs_in_bus, sic, naisc, website_address, email_address1, legal_entity_cd, credit_bureau_name, id_number, 
					federal_employer_id_number, ncci_risk_id_number, other_rating_bureau_id, created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $agency_name . '", "'
						. $street . '", "'
						. $city . '", "'
						. $state_prov_cd . '", "'
						. $postal_code . '", "'
						. $first_name . '", "'
						. $last_name . '", "'
						. $respresentative_name . '", "'
						. $office_phone . '", "'
						. $mobile_phone . '", "'
						. $fax . '", "'
						. $email . '", "'
						. $code . '", "'
						. $sub_code . '", "'
						. $customer_id . '", "'
						. $company . '", "'
						. $underwriter . '", "'
						. $applicant_name . '", "'
						. $office_phone1 . '", "'
						. $mobile_phone1 . '", "'
						. $mailing_street . '", "'
						. $mailing_city . '", "'
						. $mailing_state . '", "'
						. $mailing_zipcode . '", "'
						. $yrs_in_bus . '", "'
						. $sic . '", "'
						. $naisc . '", "'
						. $website_address . '", "'
						. $email_address1 . '", "'
						. $legal_entity_cd . '", "'
						. $credit_bureau_name . '", "'
						. $id_number . '", "'
						. $federal_employer_id_number . '", "'
						. $ncci_risk_id_number . '", "'
						. $other_rating_bureau_id . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

			$conn->query($sql);

		// ---------------------------------- Status of Submission table ------------------------------------------------ //

			$commlPolicy = $data->CommlPolicy;

			$policy_status_cd = $commlPolicy->PolicyStatusCd;
			$bound = $commlPolicy->Binder->ContractTerm->EffectiveDt;			

			$sql = 'INSERT INTO t130_status_of_submission (rq_uid, policy_status_cd, bound, created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $policy_status_cd . '", "'
						. $bound . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

			$conn->query($sql);

		// ---------------------------------- Billing Audit table ------------------------------------------------ //

			$billing_plan = $commlPolicy->BillingMethodCd;
			$payment_plan = $commlPolicy->PaymentOption->PaymentPlanCd;
			$audit = $commlPolicy->CommlPolicySupplement->AuditFrequencyCd;

			$sql = 'INSERT INTO t130_billing_audit (rq_uid, billing_plan, payment_plan, audit, created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $billing_plan . '", "'
						. $payment_plan . '", "'
						. $audit . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

			$conn->query($sql);


		// ---------------------------------- Locations table ------------------------------------------------ //

			$locations = $data->Location;

			foreach ($locations as $location) {
				$agency_id 				= 	$location->ItemIdInfo->AgencyId;
				$highest_floor 			= 	$data->WorkCompLineBusiness->WorkCompRateState->WorkCompLocInfo->HighestFloorNumberOccupied;
				$street 				= 	$location->Addr->Addr1;
				$city 					= 	$location->Addr->City;
				$county 				= 	$location->Addr->County;
				$state_prov_cd 			= 	$location->Addr->StateProvCd;
				$postal_code 			= 	$location->Addr->PostalCode;

				$sql = 'INSERT INTO t130_locations (rq_uid, agency_id, highest_floor, street, city, county, state_prov_cd, postal_code,
						created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $agency_id . '", "'
						. $highest_floor . '", "'
						. $street . '", "'
						. $city . '", "'
						. $county . '", "'
						. $state_prov_cd . '", "'
						. $postal_code . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

				$conn->query($sql);

			}

		// ---------------------------------- Policy Information table ------------------------------------------------ //

			$commlCoverages = $data->WorkCompLineBusiness->CommlCoverage;
			$commlCoverage = $commlCoverages[0];
			$workCompRateState = $data->WorkCompLineBusiness->WorkCompRateState;


			$proposed_eff_date = $commlPolicy->ContractTerm->EffectiveDt;
			$proposed_exp_date = $commlPolicy->ContractTerm->ExpirationDt;
			$normal_anniversary_rating_date = $workCompRateState->AnniversaryRatingDt;
			$participating_plan_ind = $workCompRateState->ParticipatingPlanInd;
			$retro_plan	= $workCompRateState->RetrospectiveRatingPlanCd;
			$workers_compensation = $workCompRateState->StateProvCd;

			$each_accident = $disease_policy_limit = $disease_each_employee = '';
			foreach ($commlCoverage->Limit as $limit) {
				if ($limit->LimitAppliesToCd == 'PerAcc') {
					$each_accident = $limit->FormatCurrencyAmt->Amt;
				}
				else if ($limit->LimitAppliesToCd == 'DisPol') {
					$disease_policy_limit = $limit->FormatCurrencyAmt->Amt;
				}
				else if ($limit->LimitAppliesToCd == 'DisEachEmpl') {
					$disease_each_employee = $limit->FormatCurrencyAmt->Amt;
				}
			}

			$other_states_ins = $data->WorkCompLineBusiness->OtherCoveredStateProvCd;
			$deductibles = $commlCoverage->Deductible->DeductibleAppliesToCd;
			$deductible_amount = $commlCoverage->Deductible->FormatPct;

			$other_coverages = '';
			foreach ($commlCoverages as $comml) {
				if ($comml->CoverageCd != 'WCFL') {
					$other_coverages = $other_coverages . ' ' . $comml->CoverageCd;
				}
			}

			$dividend_plan_safety_group = $data->WorkCompLineBusiness->WorkCompRateState->ParticipatingPlanDescCd;

			foreach ($data->RemarkText as $text) {
				if ($text['RefId'] == 'Insured') {
					$additional_company_information = $text;
				}
				else if ($text['RefId'] == 'RatingInformation_1') {
					$specify_additional_coverages_endorsements = $text;
				}
			}
									

			$sql = 'INSERT INTO t130_policy_information (rq_uid, proposed_eff_date, proposed_exp_date, normal_anniversary_rating_date,
					participating_plan_ind, retro_plan, workers_compensation, each_accident, disease_policy_limit, disease_each_employee,
					other_states_ins, deductibles, deductible_amount, other_coverages, dividend_plan_safety_group, additional_company_information,
					specify_additional_coverages_endorsements, created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $proposed_eff_date . '", "'
						. $proposed_exp_date . '", "'
						. $normal_anniversary_rating_date . '", "'
						. $participating_plan_ind . '", "'
						. $retro_plan . '", "'
						. $workers_compensation . '", "'
						. $each_accident . '", "'
						. $disease_policy_limit . '", "'
						. $disease_each_employee . '", "'
						. $other_states_ins . '", "'
						. $deductibles . '", "'
						. $deductible_amount . '", "'
						. $other_coverages . '", "'
						. $dividend_plan_safety_group . '", "'
						. $additional_company_information . '", "'
						. $specify_additional_coverages_endorsements . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

			$conn->query($sql);

		// ---------------------------------- Total Estimated ------------------------------------------------ //

			$annual_premium = $data->WorkCompLineBusiness->CurrentTermAmt->Amt;
			$minimum_premium = $commlPolicy->MinPremAmt->Amt;
			$deposit_premium = $commlPolicy->PaymentOption->DepositAmt->Amt;

			$sql = 'INSERT INTO t130_total_estimated (rq_uid, annual_premium, minimum_premium, deposit_premium,  created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $annual_premium . '", "'
						. $minimum_premium . '", "'
						. $deposit_premium . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

			$conn->query($sql);

		// ---------------------------------- Contact Information ------------------------------------------------ //

			$miscParty = $commlPolicy->MiscParty;

			foreach ($miscParty as $party) {
				$generalPartyInfo = $party->GeneralPartyInfo;

				$type = $party->MiscPartyInfo->MiscPartyRoleCd;
				$surname = $generalPartyInfo->NameInfo->PersonName->Surname;
				$given_name = $generalPartyInfo->NameInfo->PersonName->GivenName;
				
				$office_phone = $mobile_phone = '';

				if (is_array($generalParyInfo->Communications->PhoneInfo)) {
					foreach ($generalParyInfo->Communications->PhoneInfo as $phone) {
						if ($phone->PhoneTypeCd == 'Phone') {
							$office_phone = $phone->PhoneNumber;
						}
						else if ($phone->PhoneTypeCd == 'Cell') {
							$mobile_phone = $phone->PhoneNumber;
						}
					}
				}
				else {
					$office_phone = $generalPartyInfo->Communications->PhoneInfo->PhoneNumber;
				}

				$email = '';
				if (isset($generalPartyInfo->Communications->EmailInfo)) {
					$email = $generalPartyInfo->Communications->EmailInfo->EmailAddr;
				}
				else {
					$email = $generalPartyInfo->Communications->PhoneInfo->EmailAddr;
				}

				$sql = 'INSERT INTO t130_contact_information (rq_uid, type, surname, given_name, office_phone, mobile_phone, email,
						created_at, updated_at)
						VALUES ("'
							. $rq_uid . '", "'
							. $type . '", "'
							. $surname . '", "'
							. $given_name . '", "'
							. $office_phone . '", "'
							. $mobile_phone . '", "'
							. $email . '", "'
							. date("Y-m-d h:i:sa") . '", "'
							. date("Y-m-d h:i:sa") . '")';

				$conn->query($sql);
			}

		// ---------------------------------- Individuals Incl/Excl ------------------------------------------------ //

			$compIndividuals = $data->WorkCompLineBusiness->WorkCompIndividuals;						

			foreach ($compIndividuals as $individual) {
				$state = $individual->StateProvCd;
				$loc = '';
				$surname = $individual->NameInfo->PersonName->Surname;
				$given_name = $individual->NameInfo->PersonName->GivenName;
				$date_of_birth = $individual->BirthDt;
				$title_relationship = $individual->NameInfo->TitleRelationshipCd;
				$ownership_percent = $individual->OwnershipPct;

				$duties = '';
				if (isset($individual->DutiesDesc)) {
					$duties = $individual->DutiesDesc;
				}

				$inc_exc = $individual->IncludedExcludedCd;

				$class_code = '';
				if (isset($individual->RatingClassificationCd)) {
					$class_code = $individual->RatingClassificationCd;
				}

				$remuneration_payroll = $individual->InclIndividualsEstAnnualRemunerationAmt->Amt;

				$sql = 'INSERT INTO t130_indiv_inc_exc (rq_uid, state, loc, surname, given_name, date_of_birth, title_relationship,
						ownership_percent, duties, inc_exc, class_code, remuneration_payroll, created_at, updated_at)
						VALUES ("'
							. $rq_uid . '", "'
							. $state . '", "'
							. $loc . '", "'
							. $surname . '", "'
							. $given_name . '", "'
							. $date_of_birth . '", "'
							. $title_relationship . '", "'
							. $ownership_percent . '", "'
							. $duties . '", "'
							. $inc_exc . '", "'
							. $class_code . '", "'
							. $remuneration_payroll . '", "'
							. date("Y-m-d h:i:sa") . '", "'
							. date("Y-m-d h:i:sa") . '")';

				$conn->query($sql);
			}


		// ---------------------------------- Rating Information ------------------------------------------------ //

			$compRateState = $data->WorkCompLineBusiness->WorkCompRateState;
			$compLocInfo = $compRateState->WorkCompLocInfo;

			$state = $compRateState->StateProvCd;

			foreach ($compLocInfo as $info) {
				$loc = '';

				$compRateClass = $info->WorkCompRateClass;

				$class_code = $compRateClass->RatingClassificationCd;
				$descr_code = '';
				if (isset($compRateClass->RatingClassificationDescCd)) {
					$descr_code = $compRateClass->RatingClassificationDescCd;
				}

				$cat_dut_cls = $compRateClass->RatingClassificationDesc;
				$full_time = $compRateClass->NumEmployeesFullTime;
				$part_time = $compRateClass->NumEmployeesPartTime;

				$sic = $naics = $annual_remuneration_payroll = '';

				if (isset($compRateClass->SICCd)) {
					$sic = $compRateClass->SICCd;
				}
				if (isset($compRateClass->NAICSCd)) {
					$naics = $compRateClass->NAICSCd;
				}
				if (isset($compRateClass->Exposure)) {
					$annual_remuneration_payroll = $compRateClass->Exposure;
				}

				$rate = $compRateClass->Rate;
				$annual_manual_premium = $compRateClass->CurrentTermAmt->Amt;

				$sql = 'INSERT INTO t130_rating_information (rq_uid, loc, class_code, descr_code, cat_dut_cls, full_time, part_time,
						sic, naics, annual_remuneration_payroll, rate, annual_manual_premium, created_at, updated_at)
						VALUES ("'
							. $rq_uid . '", "'
							. $loc . '", "'
							. $class_code . '", "'
							. $descr_code . '", "'
							. $cat_dut_cls . '", "'
							. $full_time . '", "'
							. $part_time . '", "'
							. $sic . '", "'
							. $naics . '", "'
							. $annual_remuneration_payroll . '", "'
							. $rate . '", "'
							. $annual_manual_premium . '", "'
							. date("Y-m-d h:i:sa") . '", "'
							. date("Y-m-d h:i:sa") . '")';

				$conn->query($sql);
			}


		// ---------------------------------- Premium ------------------------------------------------ //

			foreach ($compRateState->CommlCoverage as $cc) {
				$state = $cc->CoverageCd;
				$factor = $cc->Limit->FormatModFactor;
				$factored_premium = $cc->CurrentTermAmt->Amt;

				$sql = 'INSERT INTO t130_premium (rq_uid, state, factor, factored_premium, created_at, updated_at)
						VALUES ("'
							. $rq_uid . '", "'
							. $state . '", "'
							. $factor . '", "'
							. $factored_premium . '", "'
							. date("Y-m-d h:i:sa") . '", "'
							. date("Y-m-d h:i:sa") . '")';

				$conn->query($sql);
			}								

		// ---------------------------------- Remarks ------------------------------------------------ //


		// ---------------------------------- Prior Carrier Information ------------------------------------------------ //

			$priorPolicy = $commlPolicy->OtherOrPriorPolicy;

			foreach ($priorPolicy as $policy) {

				if ($policy->LOBCd != 'WORK') {
					continue;
				}

				$year = substr($policy->ContractTerm->EffectiveDt, 0, 4);
				$carrier = $policy->InsurerName;
				$policy_number = $policy->PolicyNumber;
				$annual_premium = $policy->PolicyAmt->Amt;
				$mod_val = $policy->RatingFactor;
				$claims = $policy->NumLosses;
				$amount_paid = $policy->TotalPaidLossesAmt->Amt;
				$reserve = $policy->ReserveTotalAmt->Amt;

				$sql = 'INSERT INTO t130_prior_carrier_information (rq_uid, year, carrier, policy_number, annual_premium, mod_val, claims, amount_paid, reserve,
						created_at, updated_at)
						VALUES ("'
							. $rq_uid . '", "'
							. $year . '", "'
							. $carrier . '", "'
							. $policy_number . '", "'
							. $annual_premium . '", "'
							. $mod_val . '", "'
							. $claims . '", "'
							. $amount_paid . '", "'
							. $reserve . '", "'
							. date("Y-m-d h:i:sa") . '", "'
							. date("Y-m-d h:i:sa") . '")';

				$conn->query($sql);
			}

		// ---------------------------------- Nature of Business ------------------------------------------------ //

			$comment = $data->InsuredOrPrincipal->InsuredOrPrincipalInfo->BusinessInfo->OperationsDesc;

			$sql = 'INSERT INTO t130_nature_of_business (rq_uid, comment, created_at, updated_at)
					VALUES ("'
						. $rq_uid . '", "'
						. $comment . '", "'
						. date("Y-m-d h:i:sa") . '", "'
						. date("Y-m-d h:i:sa") . '")';

			$conn->query($sql);

		// ---------------------------------- General Information ------------------------------------------------ //
		
			foreach ($commlPolicy->QuestionAnswer as $qa) {

				if ($qa->QuestionCd == 'WORK07' || $qa->QuestionCd == 'WORK16' || $qa->QuestionCd == 'BOP21' || $qa->QuestionCd == 'WORK45' || $qa->QuestionCd == 'GENRL53' ||
					$qa->QuestionCd == 'WORK43' || $qa->QuestionCd == 'BOP15' || $qa->QuestionCd == 'GENRL47' || $qa->QuestionCd == 'CGL08' || $qa->QuestionCd == 'WORK11' ||
					$qa->QuestionCd == 'BOP22' || $qa->QuestionCd == 'WORK13' || $qa->QuestionCd == 'BOP23' || $qa->QuestionCd == 'WORK12' || $qa->QuestionCd == 'WORK10' || 
					$qa->QuestionCd == 'CGL29' || $qa->QuestionCd == 'BOP10' || $qa->QuestionCd == 'WORK14' || $qa->QuestionCd == 'SPEC001' || $qa->QuestionCd == 'GENRL22' || 
					$qa->QuestionCd == 'GENRL06' || $qa->QuestionCd == 'WORK08' || $qa->QuestionCd == 'CGL05' || $qa->QuestionCd == 'CGL04' || $qa->QuestionCd == 'WORK09' ||
					$qa->QuestionCd == 'GENRL14' || $qa->QuestionCd == 'WORK17') {

					$form_type = 't130_general_information';
					$question_cd = $qa->QuestionCd;
					$answer = $qa->YesNoCd;
					$explanation = $qa->Explanation;

					$sql = 'INSERT INTO question_answer (rq_uid, form_type, question_cd, answer, explanation, created_at, updated_at)
                        VALUES ("'
                            . $rq_uid . '", "'
                            . $form_type . '", "'
                            . $question_cd . '", "'
                            . $answer . '", "'
                            . $explanation . '", "'
                            . date("Y-m-d h:i:sa") . '", "'
                            . date("Y-m-d h:i:sa") . '")';

                	$conn->query($sql);
				}
			}

			$conn->close();
		}
	}
?> 
