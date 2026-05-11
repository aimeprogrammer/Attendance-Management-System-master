<?php
ob_start();
session_start();

if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

include('connect.php');

// Fetch batch attendance data
$batch_attendance_query = "
    SELECT 
        b.batch_id,
        b.batch_name,
        b.start_year,
        b.end_year,
        p.program_name,
        COUNT(DISTINCT s.st_id) as total_students,
        COUNT(DISTINCT a.stat_id) as attendance_records,
        SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) as total_present,
        SUM(CASE WHEN a.st_status = 'Absent' THEN 1 ELSE 0 END) as total_absent,
        SUM(CASE WHEN a.st_status = 'Late' THEN 1 ELSE 0 END) as total_late,
        ROUND(
            (SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) * 100.0) / 
            NULLIF(COUNT(a.stat_id), 0), 2
        ) as attendance_percentage
    FROM batches b
    LEFT JOIN programs p ON b.program_id = p.program_id
    LEFT JOIN student_enrollments se ON b.batch_id = se.batch_id
    LEFT JOIN students s ON se.st_id = s.st_id
    LEFT JOIN attendance a ON s.st_id = a.stat_id
        AND a.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY b.batch_id
    HAVING total_students > 0
    ORDER BY b.start_year DESC, attendance_percentage DESC
";

$batch_result = mysqli_query($link, $batch_attendance_query);

// Prepare data for charts
$batch_names = [];
$attendance_rates = [];
$student_counts = [];
$present_counts = [];
$absent_counts = [];
$program_names = [];

while($row = mysqli_fetch_assoc($batch_result)) {
    $batch_names[] = $row['batch_name'];
    $attendance_rates[] = floatval($row['attendance_percentage'] ?? 0);
    $student_counts[] = intval($row['total_students']);
    $present_counts[] = intval($row['total_present']);
    $absent_counts[] = intval($row['total_absent']);
    $program_names[] = $row['program_name'];
}

// Get overall statistics
$overall_stats = [
    'total_batches' => count($batch_names),
    'total_students' => array_sum($student_counts),
    'overall_avg' => count($attendance_rates) > 0 ? round(array_sum($attendance_rates) / count($attendance_rates), 2) : 0,
    'highest_batch' => count($attendance_rates) > 0 ? max($attendance_rates) : 0,
    'lowest_batch' => count($attendance_rates) > 0 ? min($attendance_rates) : 0
];

// Get batch performance ranking
$ranking_query = "
    SELECT 
        b.batch_name,
        ROUND(
            (SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) * 100.0) / 
            NULLIF(COUNT(a.stat_id), 0), 2
        ) as attendance_rate,
        CASE 
            WHEN ROUND((SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(a.stat_id), 0), 2) >= 90 THEN 'Excellent'
            WHEN ROUND((SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(a.stat_id), 0), 2) >= 75 THEN 'Good'
            WHEN ROUND((SUM(CASE WHEN a.st_status = 'Present' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(a.stat_id), 0), 2) >= 60 THEN 'Average'
            ELSE 'Needs Improvement'
        END as performance_level
    FROM batches b
    LEFT JOIN student_enrollments se ON b.batch_id = se.batch_id
    LEFT JOIN students s ON se.st_id = s.st_id
    LEFT JOIN attendance a ON s.st_id = a.stat_id
    WHERE a.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY b.batch_id
    HAVING attendance_rate IS NOT NULL
    ORDER BY attendance_rate DESC
";

$ranking_result = mysqli_query($link, $ranking_query);
$rankings = [];
while($row = mysqli_fetch_assoc($ranking_result)) {
    $rankings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Attendance Analytics - OAMS</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            /* neutral theme (match rest of app brutalist style) */
            --primary: #000;
            --success: #000;
            --danger: #000;
            --warning: #000;
            --info: #000;
            --dark: #000;
            --light: #eee;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 10px;
            font-weight: 900;
            margin: 8px 0;
            color: #000;
        }

        .stat-label {
            color: #111;
            font-size: 10px;
            font-weight: 700;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
            border-left: 4px solid var(--primary);
            padding-left: 15px;
        }

        .batch-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .batch-table th {
            background: var(--primary);
            color: white;
            padding: 15px;
            font-weight: 600;
        }

        .batch-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: var(--light);
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease;
        }

        .badge-excellent { background: #10B981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-good { background: #3B82F6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-average { background: #F59E0B; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }
        .badge-poor { background: #EF4444; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }

        .ranking-card {
            background: #fff;
            color: #000;
            border: 2px solid #000;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .ranking-item {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ranking-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 15px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animated {
            animation: fadeInUp 0.6s ease-out;
        }

        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-content">
            <!-- Header -->
            <div style="margin-bottom: 30px;">
                <h1><i class="fas fa-chart-line"></i> Student Batch vs Average Attendance Rate</h1>
                <p style="color: var(--muted);">Comprehensive analytics showing attendance patterns across different batches</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid animated">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users" style="color: var(--primary);"></i></div>
                    <div class="stat-value"><?php echo $overall_stats['total_students']; ?></div>
                    <div class="stat-label">Total Students Across Batches</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line" style="color: var(--success);"></i></div>
                    <div class="stat-value"><?php echo $overall_stats['overall_avg']; ?>%</div>
                    <div class="stat-label">Overall Attendance Rate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-trophy" style="color: var(--warning);"></i></div>
                    <div class="stat-value"><?php echo $overall_stats['highest_batch']; ?>%</div>
                    <div class="stat-label">Highest Performing Batch</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i></div>
                    <div class="stat-value"><?php echo $overall_stats['lowest_batch']; ?>%</div>
                    <div class="stat-label">Lowest Performing Batch</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-layer-group" style="color: var(--info);"></i></div>
                    <div class="stat-value"><?php echo $overall_stats['total_batches']; ?></div>
                    <div class="stat-label">Active Batches</div>
                </div>
            </div>

            <!-- Chart 1: Bar Chart - Attendance Rate by Batch -->
            <div class="chart-container animated">
                <div class="chart-title">
                    <i class="fas fa-chart-bar"></i> Attendance Rate Comparison by Batch
                </div>
                <canvas id="attendanceBarChart" style="max-height: 400px;"></canvas>
            </div>

            <!-- Chart 2: Line Chart - Trend Analysis -->
            <div class="chart-container animated">
                <div class="chart-title">
                    <i class="fas fa-chart-line"></i> Attendance vs Student Count Analysis
                </div>
                <canvas id="trendLineChart" style="max-height: 400px;"></canvas>
            </div>

            <!-- Ranking Section -->
            <div class="ranking-card animated">
                <div class="chart-title" style="color: white; border-left-color: white;">
                    <i class="fas fa-medal"></i> Batch Performance Ranking
                </div>
                <?php foreach($rankings as $index => $rank): ?>
                <div class="ranking-item">
                    <div style="display: flex; align-items: center;">
                        <div class="ranking-number">#<?php echo $index + 1; ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($rank['batch_name']); ?></strong>
                            <br>
                            <small><?php echo $rank['performance_level']; ?></small>
                        </div>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: bold;">
                        <?php echo $rank['attendance_rate']; ?>%
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Detailed Data Table -->
            <div class="batch-table animated">
                <div style="padding: 20px; background: white; border-bottom: 2px solid var(--light);">
                    <h3><i class="fas fa-table"></i> Detailed Batch Performance Report</h3>
                </div>
                <table class="table" style="width: 100%; border: 2px solid #000;">
                    <thead>
                        <tr>
                            <th style="border-right: 2px solid #000;">Batch Name</th>
                            <th style="border-right: 2px solid #000;">Program</th>
                            <th style="border-right: 2px solid #000;">Students</th>
                            <th style="border-right: 2px solid #000;">Present</th>
                            <th style="border-right: 2px solid #000;">Absent</th>
                            <th style="border-right: 2px solid #000;">Attendance</th>
                            <th style="border-right: 2px solid #000;">Performance</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Re-fetch for table display
                        $table_result = mysqli_query($link, $batch_attendance_query);
                        while($row = mysqli_fetch_assoc($table_result)): 
                            $rate = $row['attendance_percentage'] ?? 0;
                            $badge_class = 'badge-poor';
                            if($rate >= 90) $badge_class = 'badge-excellent';
                            elseif($rate >= 75) $badge_class = 'badge-good';
                            elseif($rate >= 60) $badge_class = 'badge-average';
                            
                            $bar_color = '#EF4444';
                            if($rate >= 90) $bar_color = '#10B981';
                            elseif($rate >= 75) $bar_color = '#3B82F6';
                            elseif($rate >= 60) $bar_color = '#F59E0B';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['batch_name']); ?></strong>
                                <br>
                                <small><?php echo $row['start_year'] . ' - ' . $row['end_year']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['program_name'] ?? 'N/A'); ?></td>
                            <td><span class="badge" style="background:#000;color:#fff;"><?php echo $row['total_students']; ?> students</span></td>
                            <td style="font-weight:700;"><?php echo number_format($row['total_present']); ?></td>
                            <td style="font-weight:700;"><?php echo number_format($row['total_absent']); ?></td>
                            <td><strong style="font-size: 1.1rem;"><?php echo $rate; ?>%</strong></td>
                            <td>
                                <span class="<?php echo $badge_class; ?>">
                                    <?php
                                        if($rate >= 75) echo 'Good ✓';
                                        elseif($rate >= 60) echo 'Average ⚠️';
                                        else echo 'Needs Improvement ❌';
                                    ?>
                                </span>
                            </td>
                            <td style="width: 220px;">
                                <div style="border:2px solid #000; padding:8px; border-radius:10px;">
                                    <div class="progress-bar-custom" style="background:#eee; border-radius:6px; border:2px solid #000; width: 100%; box-sizing: border-box;">
                                        <div class="progress-fill" style="width: 0%; background: <?php echo $bar_color; ?>; border-radius:4px; height: 100%; min-width: 0;" data-width="<?php echo $rate; ?>"></div>
                                    </div>
                                    <div style="margin-top:6px; font-weight:900; font-size: 0.95rem; line-height: 1.2; width: 100%; box-sizing: border-box;">
                                        <?php echo $rate; ?>% attendance rate
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Data from PHP
const batchNames = <?php echo json_encode($batch_names); ?>;
const attendanceRates = <?php echo json_encode($attendance_rates); ?>;
const studentCounts = <?php echo json_encode($student_counts); ?>;
const presentCounts = <?php echo json_encode($present_counts); ?>;
const absentCounts = <?php echo json_encode($absent_counts); ?>;

// Chart 1: Bar Chart
if(batchNames.length > 0) {
    const ctx1 = document.getElementById('attendanceBarChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: batchNames,
            datasets: [{
                label: 'Attendance Rate (%)',
                data: attendanceRates,
                backgroundColor: 'rgba(79, 70, 229, 0.7)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                borderRadius: 10,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { 
                    callbacks: { 
                        label: (ctx) => `${ctx.raw}% attendance rate` 
                    } 
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: 100, 
                    title: { display: true, text: 'Attendance Percentage (%)' },
                    grid: { color: '#e5e7eb' }
                },
                x: { 
                    title: { display: true, text: 'Academic Batches' },
                    ticks: { rotation: 45, autoSkip: true }
                }
            }
        }
    });

    // Chart 2: Line Chart
    const ctx2 = document.getElementById('trendLineChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: batchNames,
            datasets: [
                {
                    label: 'Attendance Rate (%)',
                    data: attendanceRates,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Number of Students',
                    data: studentCounts,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Attendance Rate (%)' },
                    position: 'left'
                },
                y1: {
                    beginAtZero: true,
                    title: { display: true, text: 'Number of Students' },
                    position: 'right',
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

// Animate progress bars on load
document.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.getAttribute('data-width');
        setTimeout(() => { bar.style.width = width + '%'; }, 100);
    });
});
</script>
</body>
</html>
