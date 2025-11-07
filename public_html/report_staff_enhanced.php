<?php
$page_title = 'Staff Reports';
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/controllers/daily.php';
require_once __DIR__ . '/src/controllers/user.php';
require_permission(['admin', 'manager']);

$current_user = get_logged_user();
$daily_controller = new DailyController($pdo);
$user_controller = new UserController($pdo);

$report_data = null;
$selected_staff_id = null;
$start_date = null;
$end_date = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_staff_id = $_POST['staff_id'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    if ($selected_staff_id && $start_date && $end_date) {
        $report_data = $daily_controller->get_by_staff_and_date_range($selected_staff_id, $start_date, $end_date);
        $staff_info = $user_controller->get_by_id($selected_staff_id);
    } else {
        set_message('Please select staff member and date range', 'danger');
    }
}

// Get staff members
$shop_id = is_admin() ? null : $current_user['shop_id'];
$staff_members = $user_controller->get_all('staff', $shop_id);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-chart-bar"></i> Staff Performance Reports</h1>
        </div>
    </div>
    
    <div class="content">
        <?php include __DIR__ . '/includes/messages.php'; ?>
        
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <h3>Generate Staff Report</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="staff_id">Select Staff *</label>
                            <select id="staff_id" name="staff_id" class="form-control" required>
                                <option value="">-- Select Staff Member --</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>"
                                        <?php echo ($selected_staff_id == $staff['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" required
                                   value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="end_date">End Date *</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" required
                                   value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-chart-line"></i> Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($report_data && !empty($report_data)): ?>
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3>Performance Report: <?php echo htmlspecialchars($staff_info['full_name']); ?></h3>
                    <p style="margin: 5px 0 0 0; color: #64748b;">
                        Period: <?php echo format_date($start_date); ?> to <?php echo format_date($end_date); ?>
                    </p>
                </div>
                <button onclick="window.print()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            <div class="card-body">
                <?php
                // Group by date and shop code
                $grouped_by_date = [];
                $totals_by_shop_code = [];
                $grand_totals = [
                    'cash_balance' => 0,
                    'tips' => 0,
                    'tips_calculation' => 0,
                    'opening' => 0,
                    'closing' => 0,
                    'expenses' => 0,
                    'winnings' => 0
                ];
                
                foreach ($report_data as $operation) {
                    $date = $operation['operation_date'];
                    $code = $operation['shop_code'];
                    
                    if (!isset($grouped_by_date[$date])) {
                        $grouped_by_date[$date] = [];
                    }
                    $grouped_by_date[$date][] = $operation;
                    
                    // Track totals by shop code
                    if (!isset($totals_by_shop_code[$code])) {
                        $totals_by_shop_code[$code] = [
                            'count' => 0,
                            'cash_balance' => 0,
                            'tips' => 0,
                            'tips_calculation' => 0
                        ];
                    }
                    $totals_by_shop_code[$code]['count']++;
                    $totals_by_shop_code[$code]['cash_balance'] += $operation['cash_balance'] ?? 0;
                    $totals_by_shop_code[$code]['tips'] += $operation['tips'] ?? 0;
                    $totals_by_shop_code[$code]['tips_calculation'] += $operation['tips_calculation'] ?? 0;
                    
                    // Grand totals
                    $grand_totals['cash_balance'] += $operation['cash_balance'] ?? 0;
                    $grand_totals['tips'] += $operation['tips'] ?? 0;
                    $grand_totals['tips_calculation'] += $operation['tips_calculation'] ?? 0;
                    $grand_totals['opening'] += $operation['opening_balance'] ?? 0;
                    $grand_totals['closing'] += $operation['closing_balance'] ?? 0;
                    $grand_totals['expenses'] += $operation['total_expenses'] ?? 0;
                    $grand_totals['winnings'] += $operation['total_winnings'] ?? 0;
                }
                ?>
                
                <!-- Summary by Shop Code -->
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h4 style="margin-bottom: 15px; color: #334155;">Summary by Shop Code</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($totals_by_shop_code as $code => $totals): ?>
                            <div style="background: white; padding: 15px; border-radius: 8px; border: 2px solid #3b82f6;">
                                <p style="margin: 0; font-weight: bold; font-size: 18px; color: #3b82f6;">
                                    <?php echo htmlspecialchars($code); ?>
                                </p>
                                <p style="margin: 10px 0 0 0; font-size: 14px; color: #64748b;">
                                    Operations: <?php echo $totals['count']; ?>
                                </p>
                                <p style="margin: 5px 0 0 0; font-size: 14px;">
                                    Cash Balance: <strong>₦<?php echo format_money($totals['cash_balance']); ?></strong>
                                </p>
                                <p style="margin: 5px 0 0 0; font-size: 14px;">
                                    Tips: <strong>₦<?php echo format_money($totals['tips']); ?></strong>
                                </p>
                                <p style="margin: 5px 0 0 0; font-size: 14px; color: #10b981;">
                                    Tips Calc: <strong>₦<?php echo format_money($totals['tips_calculation']); ?></strong>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Daily Operations by Date -->
                <?php foreach ($grouped_by_date as $date => $operations): ?>
                    <div style="margin-bottom: 30px;">
                        <h4 style="background: #334155; color: white; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px;">
                            <?php echo format_date($date); ?>
                            <?php if (count($operations) > 1): ?>
                                <span style="float: right; font-size: 14px;">Multiple Shop Codes (<?php echo count($operations); ?>)</span>
                            <?php endif; ?>
                        </h4>
                        
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Shop Code</th>
                                        <th>Opening</th>
                                        <th>Closing</th>
                                        <th>Expenses</th>
                                        <th>Winnings</th>
                                        <th>Cash Balance</th>
                                        <th>Tips</th>
                                        <th style="background: #ecfdf5; color: #10b981;">Tips Calculation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $daily_total_cash = 0;
                                    $daily_total_tips = 0;
                                    $daily_total_tips_calc = 0;
                                    foreach ($operations as $op): 
                                        $daily_total_cash += $op['cash_balance'] ?? 0;
                                        $daily_total_tips += $op['tips'] ?? 0;
                                        $daily_total_tips_calc += $op['tips_calculation'] ?? 0;
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($op['shop_code']); ?></strong></td>
                                            <td>₦<?php echo format_money($op['opening_balance']); ?></td>
                                            <td>₦<?php echo format_money($op['closing_balance']); ?></td>
                                            <td>₦<?php echo format_money($op['total_expenses']); ?></td>
                                            <td>₦<?php echo format_money($op['total_winnings'] ?? 0); ?></td>
                                            <td><strong>₦<?php echo format_money($op['cash_balance'] ?? 0); ?></strong></td>
                                            <td>₦<?php echo format_money($op['tips'] ?? 0); ?></td>
                                            <td style="background: #ecfdf5;"><strong style="color: #10b981;">₦<?php echo format_money($op['tips_calculation'] ?? 0); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($operations) > 1): ?>
                                        <tr style="background: #fef3c7; font-weight: bold;">
                                            <td>DAILY TOTAL</td>
                                            <td colspan="4"></td>
                                            <td>₦<?php echo format_money($daily_total_cash); ?></td>
                                            <td>₦<?php echo format_money($daily_total_tips); ?></td>
                                            <td style="background: #ecfdf5; color: #10b981;">₦<?php echo format_money($daily_total_tips_calc); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Grand Totals -->
                <div style="background: #1e293b; color: white; padding: 20px; border-radius: 8px; margin-top: 30px;">
                    <h4 style="margin-bottom: 15px;">GRAND TOTALS (<?php echo format_date($start_date); ?> - <?php echo format_date($end_date); ?>)</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div>
                            <p style="margin: 0; opacity: 0.8; font-size: 14px;">Total Cash Balance</p>
                            <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold;">
                                ₦<?php echo format_money($grand_totals['cash_balance']); ?>
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; opacity: 0.8; font-size: 14px;">Total Tips</p>
                            <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold;">
                                ₦<?php echo format_money($grand_totals['tips']); ?>
                            </p>
                        </div>
                        <div style="background: #10b981; padding: 15px; border-radius: 8px;">
                            <p style="margin: 0; opacity: 0.9; font-size: 14px;">Total Tips Calculation</p>
                            <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold;">
                                ₦<?php echo format_money($grand_totals['tips_calculation']); ?>
                            </p>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);">
                        <div>
                            <p style="margin: 0; opacity: 0.8; font-size: 12px;">Total Opening</p>
                            <p style="margin: 5px 0 0 0; font-size: 16px;">₦<?php echo format_money($grand_totals['opening']); ?></p>
                        </div>
                        <div>
                            <p style="margin: 0; opacity: 0.8; font-size: 12px;">Total Closing</p>
                            <p style="margin: 5px 0 0 0; font-size: 16px;">₦<?php echo format_money($grand_totals['closing']); ?></p>
                        </div>
                        <div>
                            <p style="margin: 0; opacity: 0.8; font-size: 12px;">Total Expenses</p>
                            <p style="margin: 5px 0 0 0; font-size: 16px;">₦<?php echo format_money($grand_totals['expenses']); ?></p>
                        </div>
                        <div>
                            <p style="margin: 0; opacity: 0.8; font-size: 12px;">Total Winnings</p>
                            <p style="margin: 5px 0 0 0; font-size: 16px;">₦<?php echo format_money($grand_totals['winnings']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($report_data !== null && empty($report_data)): ?>
        <div class="card">
            <div class="card-body">
                <p style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-chart-line" style="font-size: 48px; opacity: 0.5;"></i><br><br>
                    No operations found for the selected period.
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
