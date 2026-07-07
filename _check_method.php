<?php
require __DIR__ . '/vendor/autoload.php';

// Check if save() exists on the DomPDF PDF class
try {
    $r = new ReflectionMethod('Barryvdh\DomPDF\PDF', 'save');
    echo "save() exists\n";
} catch (Exception $e) {
    echo "save() NOT found: " . $e->getMessage() . "\n";
}

// Check what methods are available
$methods = get_class_methods('Barryvdh\DomPDF\PDF');
$pdfMethods = array_filter($methods, function($m) { return strpos($m, 'save') !== false || strpos($m, 'output') !== false || strpos($m, 'render') !== false; });
echo "PDF methods: " . implode(', ', $pdfMethods) . "\n";

// Check the parent class
$parent = get_parent_class('Barryvdh\DomPDF\PDF');
echo "Parent class: " . $parent . "\n";
$parentMethods = get_class_methods($parent);
$dompdfMethods = array_filter($parentMethods, function($m) { return strpos($m, 'save') !== false || strpos($m, 'output') !== false; });
echo "Dompdf methods: " . implode(', ', $dompdfMethods) . "\n";
