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
            <?= $this->Html->link(__('New {0}', __('Row')), ['action' => 'add'], ['class' => 'nav-link']) ?>
        </li>
    </ul>
</nav>
<div class="rows index content large-9 medium-8 columns col-sm-8 col-12">

    <h2><?= __('Rows') ?></h2>

    <div class="">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('frequency') ?></th>
                    <th><?= $this->Paginator->sort('allow_concurrent') ?></th>
                    <th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
                    <th><?= $this->Paginator->sort('modified', null, ['direction' => 'desc']) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h($row->name) ?></td>
                    <td><?= $row::types($row->type) ?></td>
                    <td><?= h($row->frequency) ?></td>
                    <td><?= $this->Format->yesNo($row->allow_concurrent) ?></td>
                    <td><?= $this->Time->nice($row->created) ?></td>
                    <td><?= $this->Time->nice($row->modified) ?></td>
                    <td class="actions">
                        <?php echo $this->Html->link($this->Icon->render('view'), ['action' => 'view', $row->id], ['escapeTitle' => false]); ?>
                        <?php echo $this->Html->link($this->Icon->render('edit'), ['action' => 'edit', $row->id], ['escapeTitle' => false]); ?>
                        <?php echo $this->Form->postLink($this->Icon->render('delete'), ['action' => 'delete', $row->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $row->id)]); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php echo $this->element('Tools.pagination'); ?>
</div>
