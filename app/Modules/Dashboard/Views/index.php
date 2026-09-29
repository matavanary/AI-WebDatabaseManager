<div class="page-container">
    <div class="page-heading dashboard-heading">
        <div><h1 class="page-title">Dashboard</h1><p>Your databases, in one place.</p></div>
        <a class="btn btn-primary" href="<?= \App\Core\Application::asset('sql') ?>"><i class="fas fa-code me-2" aria-hidden="true"></i>Open SQL Editor</a>
    </div>
    <div class="metric-strip" aria-label="Workspace overview">
        <?php foreach ([['Databases', 'db_count', 'fa-database'], ['Connections', 'connection_count', 'fa-link'], ['Users', 'user_count', 'fa-user'], ['Server version', 'version', 'fa-server']] as $metric): ?>
        <div class="metric">
            <span class="metric-icon"><i class="fas <?= $metric[2] ?>" aria-hidden="true"></i></span>
            <div><span class="metric-label"><?= $metric[0] ?></span><strong class="metric-value <?= $metric[1] === 'version' ? 'metric-version' : '' ?>"><?= $stats[$metric[1]] === null ? '—' : htmlspecialchars((string)$stats[$metric[1]]) ?></strong></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="dashboard-grid">
        <section class="card workflow-card" aria-labelledby="start-title">
            <h2 class="section-title" id="start-title">Start working</h2>
            <?php foreach ([['explorer', 'fa-database', 'Explore your data', 'Browse databases, tables and view data.'], ['sql', 'fa-code', 'Write a query', 'Open the SQL editor and run queries.'], ['builder', 'fa-diagram-project', 'Build a query', 'Use the visual query builder to create queries.']] as $action): ?>
            <a class="workflow-link" href="<?= \App\Core\Application::asset($action[0]) ?>">
                <span class="workflow-icon"><i class="fas <?= $action[1] ?>" aria-hidden="true"></i></span>
                <span><strong><?= $action[2] ?></strong><small><?= $action[3] ?></small></span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
        </section>
        <section class="card connection-summary" aria-labelledby="connection-title">
            <h2 class="section-title" id="connection-title">Active connection</h2>
            <div class="connection-name"><i class="fas fa-database" aria-hidden="true"></i><?= htmlspecialchars($connectionName) ?></div>
            <p><?php if ($connectionState === 'connected'): ?>Connected. Open Explorer to browse your databases and tables.<?php elseif ($connectionState === 'unavailable'): ?>This connection could not be reached. Check the connection settings and try again.<?php else: ?>Select a connection to explore a database server.<?php endif; ?></p>
            <?php if (\App\Core\Session::get('role') === 'administrator'): ?>
            <a class="btn btn-outline-primary w-100" href="<?= \App\Core\Application::asset('connections') ?>"><i class="fas fa-sliders me-2" aria-hidden="true"></i>Manage connections</a>
            <?php else: ?><p class="mb-0">Choose a saved connection from the menu above.</p><?php endif; ?>
        </section>
    </div>
    <section class="card recent-activity" aria-labelledby="activity-title">
        <h2 class="section-title" id="activity-title">Recent activity</h2>
        <div class="table-responsive">
            <table class="table"><thead><tr><th>Action</th><th>Database</th><th>Details</th><th>Time</th></tr></thead>
                <tbody><?php foreach ($recentActivity as $entry): ?><tr>
                    <td class="fw-medium"><?= htmlspecialchars($entry['action']) ?></td>
                    <td><?= htmlspecialchars($entry['target_database'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($entry['details'] ?: '—') ?></td>
                    <td class="text-secondary text-nowrap"><?= htmlspecialchars($entry['created_at']) ?></td>
                </tr><?php endforeach; ?></tbody>
            </table>
        </div>
        <?php if (!$recentActivity): ?><div class="empty-state"><i class="far fa-file-lines" aria-hidden="true"></i><strong><?= $activityAvailable ? 'No activity yet' : 'Activity is unavailable' ?></strong><p><?= $activityAvailable ? 'Your database activity will appear here.' : 'Check that the system database has been initialized.' ?></p></div><?php endif; ?>
    </section>
</div>
