<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $rows
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills flex-column">
        <li class="nav-item heading"><?= __('Actions') ?></li>
        <li class="nav-item">
            <?= $this->Html->link(__('New {0}', __('Row')), ['controller' => 'Rows', 'action' => 'add'], ['class' => 'nav-link']) ?>
        </li>
    </ul>
</nav>
<div class="rows index content large-9 medium-8 columns col-sm-8 col-12">

    <h2><?= __('Queue Scheduler') ?></h2>
    <p>Addon to run commands and queue tasks as crontab like database driven schedule.</p>

    <h3></h3>

</div>
