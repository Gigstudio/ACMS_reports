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
                <th><?= htmlspecialchars($col) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($staff as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= htmlspecialchars($cell) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Нет данных в таблице staff.</p>
<?php endif; ?>
