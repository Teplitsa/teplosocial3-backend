<?php

namespace Teplosocial\models;

class Certificate
{
    public static string $collection_name = 'certificates';

    const CERTIFICATE_TABLE = 'certificates';
    const CERTIFICATE_META_TABLE = 'certificatemeta';

    const CERTIFICATE_TYPE_COURSE = 'course';
    const CERTIFICATE_TYPE_TRACK = 'track';

    public static function update_item_cache(int $certificate_id, int $user_id, string $course_name, string $moment): void
    {
        $mongo_client = MongoClient::getInstance();
        $collection = $mongo_client->{MongoCache::STORAGE_NAME}->{static::$collection_name};

        $item = [
            'certificateId' => $certificate_id,
            'courseName'    => $course_name,
            'userId'        => $user_id,
            'dateTime'      => $moment,
        ];

        $updateCacheResult = $collection->findOneAndUpdate(['certificateId' => $certificate_id], ['$set' => $item]);

        // No item found to update.

        if (is_null($updateCacheResult)) {

            $updateCacheResult = $collection->insertOne($item);
        }
    }

    public static function get_item(int $certificate_id): ?object
    {
        global $wpdb;

        $table = self::CERTIFICATE_TABLE;

        $certificate = $wpdb->get_row(
            $wpdb->prepare(
                <<<SQL
                    SELECT
                        ID,
                        course_type,
                        course_name,
                        user_id,
                        user_name,
                        moment AS datetime
                    FROM
                        $wpdb->prefix{$table}
                    WHERE
                        ID = %d
                SQL,
                $certificate_id
            )
        );

        return $certificate;
    }

    public static function get_user_course_certificate(int $user_id, int $course_id): ?object
    {
        global $wpdb;

        $table = self::CERTIFICATE_TABLE;
        $meta_table = self::CERTIFICATE_META_TABLE;

        $certificate = $wpdb->get_row(
            $wpdb->prepare(
                <<<SQL
                    SELECT
                        ID,
                        course_type,
                        course_name,
                        user_id,
                        user_name,
                        moment AS datetime
                    FROM
                        $wpdb->prefix{$table} AS c
                    JOIN
                        $wpdb->prefix{$meta_table} AS cm
                        ON cm.certificate_id = c.ID
                            AND cm.meta_key = 'course_id'
                            AND cm.meta_value = %d
                    WHERE
                        user_id = %d
                        AND course_type = 'course'
                SQL,
                $course_id,
                $user_id
            )
        );

        return $certificate;
    }

    public static function get_user_track_certificate(int $user_id, int $track_id): ?object {
        global $wpdb;

        // error_log("user_id:" . $user_id);
        // error_log("track_id:" . $track_id);

        $table = self::CERTIFICATE_TABLE;
        $meta_table = self::CERTIFICATE_META_TABLE;

        $certificate = $wpdb->get_row(
            $wpdb->prepare(
                <<<SQL
                    SELECT
                        ID,
                        course_type,
                        course_name,
                        user_id,
                        user_name,
                        moment AS datetime
                    FROM
                        $wpdb->prefix{$table} AS c
                    JOIN
                        $wpdb->prefix{$meta_table} AS cm
                        ON cm.certificate_id = c.ID
                            AND cm.meta_key = 'track_id'
                            AND cm.meta_value = %d
                    WHERE
                        user_id = %d
                        AND course_type = 'track'
                SQL,
                $track_id,
                $user_id
            )
        );

        return $certificate;
    }

    public static function save_certificate($user_id, $course_name, $certificate_type, $meta = array(), $moment = null)
    {
        global $wpdb;

        if (!$moment) {
            $moment = \current_time('mysql');
        }

        $user_name = \get_user_meta($user_id, 'profile_fio', true);

        if (in_array($user_name, ["", false], true)) {
            $fname = Student::get_meta($user_id, Student::META_FIRST_NAME);
            $lname = Student::get_meta($user_id, Student::META_LAST_NAME);

            if (in_array($fname, ["", false], true) || in_array($lname, ["", false], true)) {
                $user = \get_userdata($user_id);
                $user_name = $user->display_name;
            } else {
                $user_name = "{$fname} {$lname}";
            }
        }

        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}" . self::CERTIFICATE_TABLE . "
            SET course_type = %s,
                course_name = %s,
                user_id = %d,
                user_name = %s,
                moment = %s
            ", $certificate_type, "«" . $course_name . "»", $user_id, $user_name, $moment));

        $certificate_id = $wpdb->insert_id;

        \do_action('save_certificate', $certificate_id, $user_id, $course_name, $moment);

        if ($meta) {
            self::save_certificate_meta($certificate_id, $meta);
        }

        return $certificate_id;
    }

    public static function save_certificate_meta($certificate_id, $meta)
    {
        global $wpdb;

        $insert_values = "";
        $insert_list = array();

        foreach ($meta as $k => $v) {
            $insert_list[] = $wpdb->prepare(" (%d, %s, %s) ", $certificate_id, $k, $v);
        }

        if (count($insert_list)) {

            $sql = "INSERT INTO {$wpdb->prefix}" . self::CERTIFICATE_META_TABLE . "
                (certificate_id, meta_key, meta_value)
                VALUES " . implode(", ", $insert_list);

            $wpdb->query($sql);
        }
    }

    public static function get_list(array $filter): ?array
    {
        $table = self::CERTIFICATE_TABLE;
        ['user_id' => $user_id, 'course_type' => $course_type] = $filter;

        $where_clause = "";

        if ($user_id) {
            $where_clause .= <<<SQL
                AND
                    user_id = {$user_id}
            SQL;
        }

        if($course_type && \is_array($course_type)) {
            $course_type_list_str = "'" . \implode("', '", $course_type) . "'";
            $where_clause .= <<<SQL
                AND
                    course_type IN ($course_type_list_str)
            SQL;
        }

        global $wpdb;

        $list = $wpdb->get_results(
            <<<SQL
                SELECT 
                    id AS certificateId,
                    course_name AS courseName,
                    user_id AS userId,
                    moment AS dateTime
                FROM 
                    $wpdb->prefix{$table} 
                WHERE 
                    1 = 1
                {$where_clause}
                ORDER BY
                    dateTime
                DESC
            SQL,
            \ARRAY_A
        );

        foreach ($list as &$item) {
            $item['certificateId'] = (int) $item['certificateId'];
            $item['userId'] = (int) $item['userId'];
        }

        return $list;
    }

    public static function get_pdf(int $certificate_id): string
    {
        $certificate = self::get_item($certificate_id);

        if (!class_exists('TCPDF')) {
            throw new \Exception(__('TCPDF is required.', 'tps'));
        }

        $pdf = new \TCPDF(\PDF_PAGE_ORIENTATION, \PDF_UNIT, \PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $font_fira = \TCPDF_FONTS::addTTFfont(\get_template_directory() . '/assets/fonts/PT-Root-UI_Bold.ttf');
        $font_mnts = $font_fira;
        // $font_fira = \TCPDF_FONTS::addTTFfont(\get_template_directory() . '/assets/fonts/FiraSans-SemiBold.ttf');
        // $font_mnts = \TCPDF_FONTS::addTTFfont(\get_template_directory() . '/assets/fonts/Montserrat-SemiBold.ttf');

        $pdf->SetFont($font_fira, 'B', 26, '', true);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(0, 0, 0, true);

        $pdf->SetAutoPageBreak(true, 0);

        $pdf->AddPage('L', 'A4');

        $pdf->Image(\get_template_directory() . '/assets/img/certificate.jpg', 0, 0, 297, 210, 'JPG', '', '', false, 300, '', false, false, 0, false, false, true);

        $pdf->SetXY(30, 60);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(277, 0, $certificate->user_name, 0, $ln = 0, 'L', 0, '', 0, false, 'T', 'C');

        $pdf->SetXY(30, 95);
        $pdf->MultiCell(180, 0, $certificate->course_name, 1, 'L', 1, 1, '', '', true, 0, false, true, 0);

        $pdf->SetXY(30, 132);
        $pdf->SetTextColor(154, 154, 154);
        $pdf->SetFont($font_mnts, 'B', 16, '', true);
        $pdf->MultiCell(297, 23, \date_i18n('d F Y', strtotime($certificate->datetime)) . "  /  " . 'ID #' . $certificate_id, 1, 'L', 1, 1, '', '', true, 0, false, true, 20, 'B');

        return $pdf->Output('certificate.pdf', 'D');
    }

    public static function get_pdf_v1(int $certificate_id): string
    {
        $certificate = self::get_item($certificate_id);

        if (!class_exists('TCPDF')) {
            throw new Exception(__('TCPDF is required.', 'tps'));
        }

        $pdf = new \TCPDF(\PDF_PAGE_ORIENTATION, \PDF_UNIT, \PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $font_fira = \TCPDF_FONTS::addTTFfont(\get_template_directory() . '/assets/fonts/FiraSans-SemiBold.ttf');
        $font_mnts = \TCPDF_FONTS::addTTFfont(\get_template_directory() . '/assets/fonts/Montserrat-SemiBold.ttf');
        $pdf->SetFont($font_fira, 'B', 26, '', true);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(0, 0, 0, true);

        $pdf->SetAutoPageBreak(true, 0);

        $pdf->AddPage('L', 'A4');

        $pdf->Image(\get_template_directory() . '/assets/img/certificate-old.jpg', 0, 0, 297, 210, 'JPG', '', '', false, 300, '', false, false, 0, false, false, true);

        $pdf->SetXY(0, 67);
        $pdf->SetTextColor(237, 40, 73);
        $pdf->Cell(297, 0, $certificate->user_name, 0, $ln = 0, 'C', 0, '', 0, false, 'T', 'C');
        $pdf->SetXY(60, 110);
        $pdf->MultiCell(180, 0, $certificate->course_name, 1, 'C', 1, 1, '', '', true, 0, false, true, 0);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($font_mnts, 'B', 16, '', true);
        $pdf->MultiCell(297, 23, \date_i18n('d F Y', strtotime($certificate->datetime)), 1, 'C', 1, 1, '', '', true, 0, false, true, 20, 'B');
        $pdf->SetTextColor(82, 88, 106);
        $pdf->SetFont($font_mnts, 'B', 12, '', true);
        $pdf->MultiCell(297, 0, 'ID #' . $certificate_id, 1, 'C', 1, 1, '', '', true, 0, false, true, 0);

        return $pdf->Output('certificate.pdf', 'D');
    }
}
