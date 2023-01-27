<?php use \Teplosocial\ConfigCommon; ?>

<div class="wrap">
    
<h2>Теплица.Курсы - статистика</h2>

<div class="wrap">

    <p style="margin: 20px 0 50px 0;">Выберите параметры статистики и отправьте запрос на её вычисление. Результаты будут отправлены на адреса email, указанные в соотв. поле.</p>

    <form action="#" method="get">
        <div class="tps-stats-common-settings">
            <h3>Общие настройки</h3>

            <fieldset>

                <?php
                $tps_stats_date_total_period_start = get_option('tps_stats_date_total_period_start');
                $tps_stats_date_total_period_start_timestamp = $tps_stats_date_total_period_start ?
                    strtotime($tps_stats_date_total_period_start) : strtotime('2022-10-01 00:00:00');
                ?>
                <div class="tps-field-wrapper">
                    <label for="tps-stats-date-total-period-start">Начало общего периода подсчёта</label>
                    <div class="tps-field-content">
                        <input type="text" name="date_total_period_start" class="tps-admin-datepicker" id="tps-stats-date-total-period-start" required data-min-date="-2Y" data-max-date="0" data-default-date="01.10.2022" value="<?php echo date('d.m.Y', $tps_stats_date_total_period_start_timestamp);?>" data-formatted-value-field="#tps_stats_date_total_period_start">
                    </div>
                </div>

                <div class="tps-field-wrapper">
                    <label for="tps-stats-date-from">Начало периода подсчёта</label>
                    <div class="tps-field-content">
                        <input type="text" name="date_from" class="tps-admin-datepicker" id="tps-stats-date-from" required data-min-date="-2Y" data-max-date="0" data-default-date="-7d" value="<?php echo date('d.m.Y', strtotime('-7 days'));?>" data-formatted-value-field="#tps_stats_date_from">
                    </div>
                </div>

                <div class="field">
                    <label for="tps-stats-date-to">Завершение периода подсчёта</label>
                    <div class="tps-field-content">
                        <input type="text" name="date_to" class="tps-admin-datepicker" id="tps-stats-date-to" required data-min-date="-2Y" data-max-date="0" data-default-date="0" value="<?php echo date('d.m.Y');?>" data-formatted-value-field="#tps_stats_date_to">
                    </div>
                </div>

                <div class="field">
                    <label for="tps_stats_results_emails">Список email для отправки результатов</label>
                    <div class="tps-field-content">
                        <input type="text" name="tps_stats_results_emails" id="tps_stats_results_emails" value="<?php echo implode(',', Teplosocial\Config::STATS_EXTRA_EMAILS);?>">
                    </div>
                </div>

            </fieldset>
        </div>

        <input type="hidden" name="page" value="tps_statistics">
        <input type="hidden" id="tps_stats_date_total_period_start" name="tps_stats_date_total_period_start" value="<?php echo date('Y-m-d', $tps_stats_date_total_period_start_timestamp);?>">
        <input type="hidden" id="tps_stats_date_from" name="tps_stats_date_from" value="<?php echo date('Y-m-d', strtotime('-7 days'));?>">
        <input type="hidden" id="tps_stats_date_to" name="tps_stats_date_to" value="<?php echo date('Y-m-d');?>">

        <br>
        <br>

        <div class="tps-stats-submit">
            <input type="submit" class="button-primary" name="tps_stats_submit" value="Собрать и отправить статистику">
        </div>
    </form>
</div>

</div>
