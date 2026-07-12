<?php
if (!defined('SMS_INCLUDED')) exit;
?>
        </div> <!-- End of main-content -->
    </div> <!-- End of admin-layout -->
    
    <!-- Design System UI Script -->
    <script src="<?php echo url('assets/js/ui.js'); ?>"></script>
    
    <!-- Dynamic Extra Scripts from View Component -->
    <?php if (isset($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>
