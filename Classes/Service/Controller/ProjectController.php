<?php
namespace Goettertz\BcVoting\Controller;

ini_set("display_errors", 1);

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 - 2016 Louis Göttertz <info2016@goettertz.de>, goettertz.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Revision 80
 */

use \Goettertz\BcVoting\Service\Blockchain;

/**
 * ProjectController
 */
class ProjectController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * projectRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\ProjectRepository
	 * @inject
	 */
	protected $projectRepository = NULL;

	/**
	 * votingRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\VotingRepository
	 * @inject
	 */
	protected $votingRepository;
	
	/**
	 * optionRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\OptionRepository
	 * @inject
	 */
	protected $optionRepository;
	
	/**
	 * assignmentRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\AssignmentRepository
	 * @inject
	 */
	protected $assignmentRepository = NULL;
	
	/**
	 * userRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\UserRepository
	 * @inject
	 */
	protected $userRepository = NULL;
	
	/**
	 * roleRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\RoleRepository
	 * @inject
	 */
	protected $roleRepository = NULL;
	
	
	/**
	 * categoryRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\CategoryRepository
	 * @inject
	 */
	protected $categoryRepository = NULL;
	
	/**
	 * argumentRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\ArgumentRepository
	 * @inject
	 */
	protected $argumentRepository = NULL;	
	
	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$projects = $this->projectRepository->findAll();
		$this->view->assign('projects', $projects);
	}
	
	/**
	 * action nav1
	 *
	 * @return void
	 */
	public function nav1Action() {
		$this->listAction();
	}
	
	/**
	 * action show
	 * - shows projekt-data
	 * - Abgegebene Stimmen zählen
	 * - Blockchain-Info laden
	 * - Projekt-Optionen suchen
	 * - Benutzerdaten projektbezogen laden
	 * 
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @return void
	 */
	public function showAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		
		$isAssigned = 'false';
		$isAdmin 	= 'false';
		
		$amount = 0;
		// Benutzerdaten projektbezogen laden
		
		if ($user = $this->userRepository->getCurrentFeUser()) {
// 			$blockchain = new \Goettertz\BcVoting\Service\Blockchain();
			$username = $user->getUsername();
			
			// 			if ($blockchain) {
			// 				$amount = $blockchain->getUserBalance($username, $project);
			// 			}
				
			$assignment = $user ? $project->getAssignmentForUser($user) : NULL;
			If($assignment != NULL) {
				$isAssigned = 'true';
				$this->view->assign('isAssigned', $isAssigned);
			}

				
			$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
			If($assignment != NULL) {
				$isAdmin = 'true';
				$this->view->assign('isAdmin', $isAdmin);
			}
			
			$rpcServer = $project->getRpcServer();
			if (is_string($rpcServer) && $rpcServer !== '') {
				try {
					if($bcArray = Blockchain::getRpcResult($project)->getinfo()) {
						$this->view->assign('blockchain', $bcArray);
					}
					
					if ($assets = Blockchain::getRpcResult($project)->getmultibalances('1YchWLvrTE6AA4E8avmGL394wtNSdBea3vwSvp')) {
// 						$this->view->assign('assetBalance', $assets['1YchWLvrTE6AA4E8avmGL394wtNSdBea3vwSvp']);
						
						foreach ($project->getBallots() AS $ballot) {
						
							if ($assetref = $ballot->getAsset()) {
								$this->view->assign('assetBalance', $assetref);
								foreach ($assets['1YchWLvrTE6AA4E8avmGL394wtNSdBea3vwSvp'] as $asset) {
									
									if ($assetref == $asset['assetref']) {
										$ballot->setBalance($asset['qty']);
									}
									else $ballot->setBalance(0);
								}
							}
						}
					}
// 					else $this->view->assign('assetBalance', 'huhu');


				}
				catch (\Exception $e) {
					// 			echo $e.' '.$project->getName();
				}
			}
			else {
				$this->view->assign('blockchain', 'No rpc.');
			}
		}		
		$this->view->assign('project', $project);
		$this->view->assign('isAdmin', $isAdmin);
		$this->view->assign('isAssigned', $isAssigned);
	}

	/**
	 * action new
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Project $newProject
	 * @ignorevalidation $newProject
	 * @return void
	 */
	public function newAction(\Goettertz\BcVoting\Domain\Model\Project $newProject = NULL) {
			
		if ($user = $this->userRepository->getCurrentFeUser()) {
			
			$this->view->assign('newProject', $newProject);
		}
		else {
			
			$this->addFlashMessage('You aren\'t currently logged in! Please goto <a href="/login/">login</a> or <a href="/register/">register</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			$this->redirect('list');
		}
		
	}

	/**
	 * action create
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Project $newProject
	 * 
	 * @return void
	 */
	public function createAction(\Goettertz\BcVoting\Domain\Model\Project $newProject) {
		
		if ($user = $this->userRepository->getCurrentFeUser()) {
			
			# gets correct UNIX timestamp only if contained in formdata
			$start = strtotime($newProject->getStart()); $end = strtotime($newProject->getEnd());
			if($start > 0) $newProject->setStart($start); if ($end > 0) $newProject->setEnd($end);
				
			
			$this->projectRepository->add($newProject);
			$this->addFlashMessage('The project "'.$newProject->getName().'" was created.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
				
			$roles = $this->roleRepository->findByName('admin');
				
			if (count($roles) == 0) {
				$newRole = new \Goettertz\BcVoting\Domain\Model\Role();
				$newRole->setName('admin');
				$this->roleRepository->add($newRole);
				$roles[0] = $newRole;
			}

			if ($this->addAssignment($newProject, $user, $roles[0])) {
				
			}
		}
		else {
			$this->addFlashMessage('You aren\'t currently logged in!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		}

		$persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager");		
		$persistenceManager->persistAll();		

		if ($project = $this->projectRepository->findByUid($newProject->getUid())) {
			$this->redirect('createSettings', 'Project', 'BcVoting', array('project' => $project));
		}
		else {
			$this->redirect('list');
		}
  	
	}
	
	/**
	 * action createSettings
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * 
	 * @return void
	 */
	public function createSettingsAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		$this->settingsAction($project);
	}
	
	/**
	 * action settings
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * 
	 * @return void
	 */
	public function settingsAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		if ($user = $this->userRepository->getCurrentFeUser()) {			
			$assignment = $user ? $project->getAssignmentForUser($user,'admin') : NULL;
			If($assignment != NULL) {
				
				$categories = $this->categoryRepository->findAll();
				
				$this->view->assign('categories', $categories);
				$this->view->assign('project', $project);
				$this->view->assign('isAdmin', 'true');
			}
		}
	}
	
	/**
	 * action edit
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @ignorevalidation $project
	 * @return void
	 */
	public function editAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		
 		# Get the user assignment and throw an exception if the current user is not a
 		# member of the selected project.
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$isAssigned = false;
			$assignment = $user ? $project->getAssignmentForUser($user,'admin') : NULL;
			If($assignment != NULL) {
				$isAssigned = true;
				$role = $assignment->getRole($assignment);
				$roleName = $role->getName($role);
				$categories = $this->categoryRepository->findAll();
				$this->view->assignMultiple(array('project' => $project, 'assigned' => $isAssigned, 'role' => $roleName, 'categories' => $categories));
			}
			else {
				die('No admin!');
			}
		}
		else {
			die('Not allowed!');
		}
	}
	
	/**
	 * action editbcparams -Blockchain-Parameter e.g. Blockchain name, rpc etc.
	 * 
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @ignorevalidation $project
	 * @return void
	 */
	public function editbcparamsAction(\Goettertz\BcVoting\Domain\Model\Project $project)
	{
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$isAssigned = false;
			$assignment = $user ? $project->getAssignmentForUser($user,'admin') : NULL;
			If($assignment != NULL) {
				
				$rpcServer = $project->getRpcServer();
				if (is_string($rpcServer) && $rpcServer !== '') {
					try {
						if(is_array($this->getReul($project)->getinfo())) {
							$bcArray = $this->getReul($project)->getinfo();
							$this->view->assign('blockchain', $bcArray);
						}
					}
					catch (\Exception $e) {
						// 			echo $e.' '.$project->getName();
					}
				}
				else {
					$this->view->assign('blockchain', NULL);
				}
				
				$this->view->assign('project', $project);
			}
			else {
				die('No admin!');
			}
		}
		else {
			die('Not allowed!');
		}
	}

	/**
	 * action update
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * 
	 * @return void
	 */
	public function updateAction(\Goettertz\BcVoting\Domain\Model\Project $project) {

		// Nur update, wenn login noch möglich
		if ($user = $this->userRepository->getCurrentFeUser()) {

			$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
			If($assignment != NULL) {
				
				# gets correct UNIX timestamp only if contained in formdata
				$start = strtotime($project->getStart()); $end = strtotime($project->getEnd());
				if($start > 0) $project->setStart($start); if ($end > 0) $project->setEnd($end);
								
				$this->projectRepository->update($project);
				$this->addFlashMessage('The object was updated', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
			}
			else {
				$this->addFlashMessage('You\'re no admin!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			}
		}		
		else {
			$this->addFlashMessage('You\'re not logged in!!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		}
		if ($this->request->hasArgument('redirect')) {
			$redirect = $this->request->getArgument('redirect');
			if (is_array($redirect)) {
				$this->redirect($redirect['action'],$redirect['controller'],$redirect['extension'], array('project' => $project));
			}			
		}
		$this->redirect('edit','Project','BcVoting',array('project'=>$project));
	}

	/**
	 * action delete
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @return void
	 */
	public function deleteAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$isAssigned = false;
			$assignment = $user ? $project->getAssignmentForUser($user,'admin') : NULL;
			If($assignment != NULL) {
				$this->addFlashMessage('The object was deleted.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
				$this->projectRepository->remove($project);
			}
			else {
				$this->addFlashMessage('You\'re no admin!!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			}			
		}
		else {
			$this->addFlashMessage('You\'re not logged in!!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		}
		$this->redirect('list');
	}

	/**
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 */
	public function removeLogoAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		$sql = 'UPDATE sys_file_reference SET deleted=1 WHERE tablenames=\'tx_bcvoting_domain_model_ptoject\' AND fieldname=\'logo\' AND uid_foreign = '.$ptoject->getUid().' AND deleted = 0';
		$db = $GLOBALS['TYPO3_DB']->sql_query($sql);
		$this->redirect('edit','Ptoject','BcVoting',array('ptoject'=>$ptoject));
	}
		
	/**
	 * action assign
	 * 
	 * assigns an user to a project as a member
	 * 
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 */
	public function assignAction(\Goettertz\BcVoting\Domain\Model\Project $project, \Goettertz\BcVoting\Domain\Model\User $user = NULL) {
		$user = $this->userRepository->getCurrentFeUser();
		if ($user === NULL) {
			$loginPid = $this->settings['login'];
			$registrationPid = $this->settings['registration'];
			
			$this->view->assign('project', $project);
			$this->view->assign('login', $loginPid);
			$this->view->assign('register', $registrationPid);
		}
		else {
			//Prüfen, ob bereits Mitglied
			if (!$assignment = $user ? $project->getAssignmentForUser($user) : NULL) {
				
				// Falls noch keine Rolle member vorhanden ist
				$roles = $this->roleRepository->findByName('Member');
				if (count($roles) == 0) {
					$newRole = new \Goettertz\BcVoting\Domain\Model\Role();
					$newRole->setName('Member');
					$this->roleRepository->add($newRole);
					$roles[0] = $newRole;
				}
				
				# Mitglied als Member registrieren 	
				try {
					$this->addAssignment($project, $user, $roles[0]);
				} catch (Exception $e) {
					$this->addFlashMessage($e, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				}
					
				
				# VTC für Transaktionen bereitstellen ... 
				$blockchain = new \Goettertz\BcVoting\Service\Blockchain();
				$newAddress = $blockchain->getReul($project)->getaccountaddress($user->getUsername());
				if ($bcArray = $blockchain->getReul($project)->sendtoaddress($newAddress,0.1)) {
					$this->addFlashMessage('Send 0.1 VTC...ok! Please be patient, while waiting for confirmation. <br />'.$bcArray, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
				}
				else {
					$this->addFlashMessage('Send 0.1 VTC...failed!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				}
					
				$this->addFlashMessage('You are assigned to this project!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);				
			}
			$this->redirect('show',NULL,NULL,array('project' => $project, 'user' => $user));
		}
	}
	
	/**
	 * action arguments
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 *
	 * @return void
	 */
	public function argumentsAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		$arguments = $this->argumentRepository->findByProject($project);
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$this->view->assign('user', $user);
			$assignment = $user ? $project->getAssignmentForUser($user,'admin') : NULL;
			If($assignment != NULL) {
				$this->view->assign('assigned', true);
				$this->view->assign('assignment', $assignment);
				$this->view->assign('project', $project);
				$this->view->assign('arguments', $arguments);	
			}
		}
	}
	
		/**
		 * adds Assignment
		 * 
		 * @param \Goettertz\BcVoting\Domain\Model\Project $project;
		 * @param \Goettertz\BcVoting\Domain\Model\User $user;
		 * @param string $role
		 * 
		 * @return void
		 */
		protected function addAssignment(\Goettertz\BcVoting\Domain\Model\Project $project, \Goettertz\BcVoting\Domain\Model\User $user, $role) 
			{
			$assignment = New \Goettertz\BcVoting\Domain\Model\Assignment();				
			$assignment->setProject($project);
			$assignment->setUser($user);
			$assignment->setRole($role);
			$assignment->setVotes(1);

			try {
				$this->assignmentRepository->add($assignment);
			} catch (Exception $e) {
				die($e);		
			}
		}
			
			
		/**
		 * 
		 * @param \Goettertz\BcVoting\Domain\Model\Project $project
		 * @return void
		 */
		public function evaluationAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
			$bc = new \Goettertz\BcVoting\Service\OP_RETURN($project->getRpcPassword(), $project->getRpcPort());
			if ($this->request->hasArgument('voting')) {
				$voting = $this->request->getArgument('voting');
				if ($reference = trim($voting['reference'])) {
					$this->view->assign('reference', $reference);
				}
				else {
					$this->addFlashMessage('No reference!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				}
				
				// Hash aus BC		
				$hashbc = 0;
				if ($opr = $bc->OP_RETURN_retrieve($reference)) {
					if ($hashBc = $opr[0]['data']) {
						$this->view->assign('hashbc', $hashBc);
						
						// Daten aus DB
						$hash = 0;
						$secret = 0;
						
						if ($votes = $this->votingRepository->findByReference($reference)) {
							if (count($votes) > 0) {
								$vote = $votes[0];
								$secret = $vote->getSecret();
								$hash = hash('md5',$secret);
							}
							else {
								$this->addFlashMessage('Reference not found in DB!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
							}
						}
					}
					else {
						$this->addFlashMessage('No checksum in blockchain-data!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
					}					
				}
				else {
					$this->addFlashMessage('Reference not found!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				}

				$this->view->assign('secret', $secret);
				$this->view->assign('hash', $hash);
				$this->view->assign('voting', $voting);
			}
 			else 
			{
			
				$result = array();
				$votes = $this->votingRepository->findByProject($project);
				$result['count'] = count($votes);
				
				$i = 0;
				$values = array();
				foreach ($votes as $vote) {
					
// 					$bc = new \Goettertz\BcVoting\Service\OP_RETURN();
					$mcrypt = new \Goettertz\BcVoting\Service\MCrypt();
					
					$ref = $vote->getReference();
					$secret = $vote->getSecret();
					$hash = hash('md5',$secret);
					$opr = $bc->OP_RETURN_retrieve($ref);
					$hashBc = $opr[0]['data'];
								
					if ($hashBc == $hash) { //Ist gültig
						$result['votes'][$ref]['result'] = $mcrypt->decrypt($secret);
						$result['votes'][$ref]['ref'] = $ref;
						$result['votes'][$ref]['hashbc'] = $hashBc;
						$values[$i] = $mcrypt->decrypt($secret);
					}
					else {
						$result['votes'][$ref]['result'] = 'wrong!';
						$result['votes'][$ref]['ref'] = $ref;
						$result['votes'][$ref]['hashbc'] = $hashBc;
						$result['votes'][$ref]['error'] = $opr['error'];
						$result['votes'][$ref]['opr'] = $opr;
					}
				$i++;
				}
				$counts = array_count_values($values);
				$votings = array();
				$i = 0;
				foreach ($counts as $key => $value) {
					$counts[$key] = $value;
					$votings[$i]['name'] = $key;
					$votings[$i]['counts'] = $value;
					$votings[$i]['width'] = $value*10;
					$i++;
				}
				
				usort($votings, function ($a, $b) {
					return $b['counts'] - $a['counts'];
				});
				// Benutzerdaten projektbezogen laden
				$isAssigned = false;
				$amount = 0;
			}
			
			if ($user = $this->userRepository->getCurrentFeUser()) {
				$username = $user->getUsername();
				if ($blockchain) {
					$amount = $blockchain->getUserBalance($username, $project);
				}
					
				$assignment = $user ? $project->getAssignmentForUser($user) : NULL;
				If($assignment != NULL) {
					$isAssigned = true;
					$role = $assignment->getRole($assignment);
					$roleName = $role->getName($role);
				}
			}
				
			$this->view->assign('project', $project);
			$this->view->assign('counts', $votings);
			$this->view->assign('results', $result);
			$this->view->assign('assigned', $isAssigned);
			$this->view->assign('role', $roleName);
			$this->view->assign('user', $user);
		}
		
		

	}
?>