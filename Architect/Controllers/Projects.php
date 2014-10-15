<?php
namespace Architect\Controllers;

use \Architect\Core;
use \Architect\ORM\src\Project;
use \Architect\ResponseCode;
use \Architect\Result;
use \Architect\Request;

/**
 * Architect\Controllers\Project
 *
 * Projects controler
 *
 * @category Controllers
 * @package Architect
 * @subpackage Controllers
 * @author Rob Lowcock <rob.lowcock@gmail.com>
 */
class Projects extends ControllerAbstract
{
	/**
	 * Read a single project or list of projects
	 * @param  int $id
	 * @return array
	 */
	public function read($id = 0)
	{
		if (!empty($id)) {
			$project = $this->orm->find('\Architect\ORM\src\Project', $id);

			if (empty($project)) {
				return new Result(ResponseCode::RESOURCE_NOT_FOUND);
			}

			$context = $project->getContext();

			return new Result(ResponseCode::OK, array(
				'project_id' => $project->getId(),
				'project_name' => $project->getProjectName(),
				'project_description' => $project->getProjectDescription(),
				'context' => !empty($context) ? $context : false,
				'tasks' => $this->_returnTasks($project->getTasks()),
				'created' => $project->getCreated(),
				'updated' => $project->getUpdated(),
			));
		} else {
			$repository = $this->orm->getRepository('\Architect\ORM\src\Project');
			$projects = $repository->findAll();

			$result = array();

			foreach ($projects as $project) {
				$context = $project->getContext();

				$result[] = array(
					'project_id' => $project->getId(),
					'project_name' => $project->getProjectName(),
					'project_description' => $project->getProjectDescription(),
					'context' => !empty($context) ? $context : false,
					'created' => $project->getCreated(),
					'updated' => $project->getUpdated(),
				);
			}

			return new Result(ResponseCode::OK, $result);
		}
	}

	/**
	 * Create a new project
	 * @return array
	 */
	public function create()
	{
		$project = new Project();
		$project->setProjectName($this->container['request']->get('project_name'));
		$project->setProjectDescription($this->container['request']->get('project_description'));
		$context_id = $this->container['request']->get('context_id');

		if (!empty($context_id)) {
			$context = $this->orm->find('\Architect\ORM\src\Context', $context_id);
		} else {
			$context = null;
		}

		$project->setContext($context);
		$project->setCreated();
		$project->setUpdated();

		$this->orm->persist($project);
		$this->orm->flush();

		Core::$app->response->headers->set('Location', Core::$app->request->getPath() . '/' . $project->getId());

		return new Result(
			ResponseCode::OK,
			array(
				'project_id' => $project->getId(),
				'project_name' => $project->getProjectName(),
			)
		);
	}

	/**
	 * Update a project
	 * @param  int $id
	 * @return array
	 */
	public function update($id)
	{
		$project = $this->orm->find('\Architect\ORM\src\Project', $id);

		if (empty($project)) {
			return new Result(ResponseCode::RESOURCE_NOT_FOUND);
		}

		$context_id = $this->container['request']->get('context_id');

		if (!empty($context_id)) {
			$context = $this->orm->find('\Architect\ORM\src\Context', $context_id);
		} else {
			$context = null;
		}

		$project->setContext($context);
		$project->setUpdated();

		$project->setProjectName($this->container['request']->get('project_name'));
		$project->setProjectDescription($this->container['request']->get('project_description'));
		$this->orm->persist($project);
		$this->orm->flush();

		return new Result(
			ResponseCode::OK,
			array(
				'project_id' => $project->getId(),
				'project_name' => $project->getProjectName(),
			)
		);
	}

	/**
	 * Delete a project
	 * @param  int $id
	 * @return array
	 */
	public function delete($id)
	{
		$project = $this->orm->find('\Architect\ORM\src\Project', $id);

		if (empty($project)) {
			return new Result(ResponseCode::RESOURCE_NOT_FOUND);
		}

		$this->orm->remove($project);
		$this->orm->flush();

		return new Result(
			ResponseCode::OK,
			array(
				'success' => true,
			)
		);
	}
}