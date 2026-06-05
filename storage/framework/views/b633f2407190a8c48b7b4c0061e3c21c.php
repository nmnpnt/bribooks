<?php if($deprecated !== false): ?>
<?php ($text = $deprecated === true ? 'deprecated' : "deprecated:$deprecated"); ?>
<?php $__env->startComponent('scribe::components.badges.base', ['colour' => 'darkgoldenrod', 'text' => $text]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php /**PATH C:\wamp64\www\bribooks-laravel\vendor\knuckleswtf\scribe\src/../resources/views//components/badges/deprecated.blade.php ENDPATH**/ ?>