<?php

/**
 * Sample 3 - Conditional Logic and Control Structures
 *
 * This example demonstrates:
 * - Using if/elseif/else blocks for conditional rendering
 * - Using ifnot blocks for negative conditions
 * - Role-based text output (Admin vs. User)
 */

use OdtTemplateEngine\OdtTemplate;

// [1] Initialize template engine with a logic-focused ODT template
$template = new OdtTemplate('samples/templates/template_03_logic_elements.odt');

// [2] Load the template into memory
$template->load();

// [3] Assign variables that drive the conditional logic

// 'price' determines the customer category based on amount
$template->assign([
    'price' => 120, // You can change this to 70 or 30 to test different cases
]);

// 'is_admin' controls access and greetings
$template->assign([
    'is_admin' => false, // Set to true or false to test admin/user views
]);

// [4] Render all assigned variables into the template
$template->render();

// [5] Save the final output document
$template->save('samples/output/output_03_logic_elements.odt');
