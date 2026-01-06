<?php
function v($value, $placeholder = 'N/A') {
    if (
        $value === null ||
        $value === '' ||
        $value === '0000-00-00' ||
        $value === '0000-00-00 00:00:00'
    ) {
        return '<span class="text-muted">'.$placeholder.'</span>';
    }
    return htmlspecialchars($value);
}
