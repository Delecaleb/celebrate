<?php
foreach (['gifts','wish_contributions'] as $t) {
    echo "=== $t ===\n";
    foreach (DB::select("SHOW COLUMNS FROM $t") as $c) {
        printf("%-26s %-22s null=%s\n", $c->Field, $c->Type, $c->Null);
    }
}
