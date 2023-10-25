<?php
declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use QueueScheduler\Controller\AppController;

/**
 * Rows Controller
 *
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\Row> paginate($object = null, array $settings = [])
 * @property \QueueScheduler\Model\Table\RowsTable $Rows
 */
class RowsController extends AppController {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->viewBuilder()->setHelpers([
			'Tools.Format',
			'Tools.Icon',
		]);
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$rows = $this->paginate($this->Rows);

		$this->set(compact('rows'));
	}

	/**
	 * View method
	 *
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function view($id = null) {
		$row = $this->Rows->get($id, [
			'contain' => [],
		]);

		$this->set(compact('row'));
	}

	/**
	 * Add method
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
	 */
	public function add() {
		$row = $this->Rows->newEmptyEntity();
		if ($this->request->is('post')) {
			$row = $this->Rows->patchEntity($row, $this->request->getData());
			if ($this->Rows->save($row)) {
				$this->Flash->success(__('The row has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The row could not be saved. Please, try again.'));
		}
		$this->set(compact('row'));
	}

	/**
	 * Edit method
	 *
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
	 */
	public function edit($id = null) {
		$row = $this->Rows->get($id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$row = $this->Rows->patchEntity($row, $this->request->getData());
			if ($this->Rows->save($row)) {
				$this->Flash->success(__('The row has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The row could not be saved. Please, try again.'));
		}
		$this->set(compact('row'));
	}

	/**
	 * Delete method
	 *
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$row = $this->Rows->get($id);
		if ($this->Rows->delete($row)) {
			$this->Flash->success(__('The row has been deleted.'));
		} else {
			$this->Flash->error(__('The row could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
