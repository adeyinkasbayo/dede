<?php
$message_data = get_message();
if ($message_data):
    $type_class = 'alert-' . $message_data['type'];
?>
<div class="alert <?php echo $type_class; ?>">
    <span><?php echo htmlspecialchars($message_data['message']); ?></span>
    <span class="close" onclick="closeAlert(this)">&times;</span>
</div>
<?php endif; ?>