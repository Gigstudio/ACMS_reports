<h2><?= $title ?></h2>
<!-- <?php if (isset($firebird_tables)): ?>
    <h3>Таблицы Firebird:</h3>
    <ul>
        <?php foreach ($data['firebird_tables'] as $table): ?>
            <li><?= htmlspecialchars($table) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?> -->

<?php if (!empty($staff)): ?>
    <table>
        <thead>
        <tr>
            <?php foreach (array_keys($staff[0]) as $col): ?>
                <th><?= htmlspecialchars((string)($col ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($staff as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= htmlspecialchars((string)($cell ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Нет данных в таблице staff.</p>
<?php endif; ?>
