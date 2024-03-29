<?php declare(strict_types=1);

namespace QueueScheduler\Controller\Admin;

use QueueScheduler\Controller\AppController;

/**
 * Rows Controller
 *
 * @method \Cake\Datasource\ResultSetInterface<\QueueScheduler\Model\Entity\SchedulerRow> paginate($object = null, array $settings = [])
 * @property \QueueScheduler\Model\Table\SchedulerRowsTable $SchedulerRows
 */
class SchedulerRowsController extends AppController {

	use LoadHelperTrait;

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadHelpers();
	}

	/**
	 * Index method
	 *
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$rows = $this->paginate($this->SchedulerRows);

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
		$row = $this->SchedulerRows->get($id, [
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
		$row = $this->SchedulerRows->newEmptyEntity();
		if ($this->request->is('post')) {
			$row = $this->SchedulerRows->patchEntity($row, $this->request->getData());
			if ($this->SchedulerRows->save($row)) {
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
		$row = $this->SchedulerRows->get($id, [
			'contain' => [],
		]);
		if ($this->request->is(['patch', 'post', 'put'])) {
			$row = $this->SchedulerRows->patchEntity($row, $this->request->getData());
			if ($this->SchedulerRows->save($row)) {
				$this->Flash->success(__('The row has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The row could not be saved. Please, try again.'));
		}
		$this->set(compact('row'));
	}

	/**
	 * @param string|null $id Row id.
	 *
	 * @return \Cake\Http\Response|null|void Redirects to index.
	 */
	public function run($id = null) {
		$this->request->allowMethod(['post']);
		$row = $this->SchedulerRows->get($id);

		if ($this->SchedulerRows->run($row)) {
			$this->Flash->success(__('The job has been added to the queue.'));
		} else {
			$this->Flash->error(__('The job could not be added to the queue.'));
		}

		return $this->redirect($this->referer(['action' => 'view', $id]));
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
		$row = $this->SchedulerRows->get($id);
		if ($this->SchedulerRows->delete($row)) {
			$this->Flash->success(__('The row has been deleted.'));
		} else {
			$this->Flash->error(__('The row could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
