<?php
/**
 * Patient Classification Helper for Barangay Rural Health Unit (HealthLogs)
 */

class PatientClassifier
{
    public static function getClassifications(): array
    {
        return [
            'infant' => [
                'label' => 'Infant / Baby (0-11m)',
                'short_label' => 'Baby',
                'description' => 'Babies aged 0 to 11 months eligible for EPI routine immunizations, newborn screening, and growth monitoring.',
                'badge' => 'bg-pink-100 text-pink-800 border-pink-200',
                'icon' => 'fa-baby',
                'priority' => true,
                'category' => 'Pediatric'
            ],
            'under_five' => [
                'label' => 'Under-Five Child (1-4y)',
                'short_label' => 'Under-5',
                'description' => 'Children aged 1 to 4 years eligible for ECCD, biannual Vitamin A, deworming, and nutrition monitoring.',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                'icon' => 'fa-child',
                'priority' => true,
                'category' => 'Pediatric'
            ],
            'school_age' => [
                'label' => 'School-Aged Child (5-9y)',
                'short_label' => 'School-Aged',
                'description' => 'Children aged 5 to 9 years eligible for school health assessments, vision screening, and oral health.',
                'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
                'icon' => 'fa-school',
                'priority' => false,
                'category' => 'Pediatric'
            ],
            'adolescent' => [
                'label' => 'Adolescent (10-19y)',
                'short_label' => 'Adolescent',
                'description' => 'Youth aged 10 to 19 years eligible for adolescent reproductive health counseling, HPV immunization, and mental wellness.',
                'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'icon' => 'fa-user-graduate',
                'priority' => false,
                'category' => 'Adolescent'
            ],
            'pregnant' => [
                'label' => 'Pregnant Mother (Maternal)',
                'short_label' => 'Pregnant',
                'description' => 'Expectant mothers eligible for 4-antenatal visits, tetanus toxoid, iron-folic acid, and facility delivery planning.',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
                'icon' => 'fa-person-pregnant',
                'priority' => true,
                'category' => 'Maternal'
            ],
            'postpartum' => [
                'label' => 'Postpartum / Lactating Mother',
                'short_label' => 'Postpartum',
                'description' => 'Mothers within 6 weeks post-delivery eligible for postnatal checkups, lactation counseling, and postpartum family planning.',
                'badge' => 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-200',
                'icon' => 'fa-hands-holding-child',
                'priority' => true,
                'category' => 'Maternal'
            ],
            'adult' => [
                'label' => 'Adult (20-59y)',
                'short_label' => 'Adult',
                'description' => 'General working-age adults eligible for outpatient consultation, family planning, and PhilPEN screening.',
                'badge' => 'bg-slate-100 text-slate-800 border-slate-200',
                'icon' => 'fa-user',
                'priority' => false,
                'category' => 'Adult'
            ],
            'senior' => [
                'label' => 'Senior Citizen (60+y)',
                'short_label' => 'Senior (60+)',
                'description' => 'Older persons aged 60+ eligible for geriatric assessments, flu/pneumococcal vaccines, and free NCD maintenance meds.',
                'badge' => 'bg-purple-100 text-purple-800 border-purple-200',
                'icon' => 'fa-person-cane',
                'priority' => true,
                'category' => 'Geriatric'
            ],
            'pwd' => [
                'label' => 'Person with Disability (PWD)',
                'short_label' => 'PWD',
                'description' => 'Patients with recognized long-term physical, mental, intellectual, or sensory impairments.',
                'badge' => 'bg-teal-100 text-teal-800 border-teal-200',
                'icon' => 'fa-wheelchair',
                'priority' => true,
                'category' => 'Priority Group'
            ],
            'indigent_4ps' => [
                'label' => '4Ps / Indigent Priority',
                'short_label' => '4Ps Indigent',
                'description' => 'Beneficiaries of the Pantawid Pamilyang Pilipino Program and DSWD-certified indigent households.',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'icon' => 'fa-hand-holding-heart',
                'priority' => true,
                'category' => 'Priority Group'
            ]
        ];
    }

    public static function formatAge(string $birthDate): string
    {
        try {
            $dob = new DateTime($birthDate);
            $today = new DateTime();
            $diff = $dob->diff($today);

            if ($diff->invert || ($diff->y === 0 && $diff->m === 0 && $diff->d === 0)) {
                return 'Newborn (< 1 day)';
            }

            if ($diff->y === 0) {
                if ($diff->m === 0) {
                    return $diff->d . ' ' . ($diff->d === 1 ? 'day old' : 'days old');
                }
                $daysPart = $diff->d > 0 ? " {$diff->d}d" : '';
                return $diff->m . ' ' . ($diff->m === 1 ? 'mo' : 'mos') . $daysPart . ' old';
            }

            if ($diff->y < 5) {
                $monthPart = $diff->m > 0 ? " {$diff->m}m" : '';
                return $diff->y . ' ' . ($diff->y === 1 ? 'yr' : 'yrs') . $monthPart . ' old';
            }

            return $diff->y . ' yrs old';
        } catch (Throwable $e) {
            return '—';
        }
    }

    public static function getAgeInYears(string $birthDate): int
    {
        try {
            $dob = new DateTime($birthDate);
            $today = new DateTime();
            return (int)$dob->diff($today)->y;
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function getAgeInMonths(string $birthDate): int
    {
        try {
            $dob = new DateTime($birthDate);
            $today = new DateTime();
            $diff = $dob->diff($today);
            return ($diff->y * 12) + $diff->m;
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function detectClassification(string $birthDate, string $sex, bool $isPregnant = false, bool $isPwd = false, bool $is4ps = false): string
    {
        if ($isPwd) {
            return 'pwd';
        }
        if ($isPregnant && strtolower($sex) === 'female') {
            return 'pregnant';
        }

        $months = self::getAgeInMonths($birthDate);
        $years = self::getAgeInYears($birthDate);

        if ($months < 12) {
            return 'infant';
        }
        if ($years < 5) {
            return 'under_five';
        }
        if ($years < 10) {
            return 'school_age';
        }
        if ($years < 20) {
            return 'adolescent';
        }
        if ($years >= 60) {
            return 'senior';
        }
        if ($is4ps) {
            return 'indigent_4ps';
        }

        return 'adult';
    }

    public static function getInfo(string $key): array
    {
        $all = self::getClassifications();
        return $all[$key] ?? [
            'label' => ucfirst(str_replace('_', ' ', $key)),
            'short_label' => ucfirst($key),
            'description' => '',
            'badge' => 'bg-slate-100 text-slate-800 border-slate-200',
            'icon' => 'fa-user',
            'priority' => false,
            'category' => 'General'
        ];
    }

    public static function renderBadge(string $classification, ?string $birthDate = null): string
    {
        $info = self::getInfo($classification);
        $iconHtml = "<i class=\"fas {$info['icon']} text-[10px] mr-1\"></i>";
        $ageText = $birthDate ? ' • ' . self::formatAge($birthDate) : '';
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {$info['badge']}\" title=\"{$info['label']}\">{$iconHtml}{$info['short_label']}{$ageText}</span>";
    }
}
