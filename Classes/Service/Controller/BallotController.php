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


use Goettertz\BcVoting\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Goettertz\BcVoting\Service\Blockchain;

/**
 * BallotController
 */
class BallotController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
	
	/**
	 * userRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\UserRepository
	 * @inject
	 */
	protected $userRepository = NULL;	

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
	protected $votingRepository = NULL;	
	
	/**
	 * assignmentRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\AssignmentRepository
	 * @inject
	 */
	protected $assignmentRepository = NULL;

	/**
	 * ballotRepository
	 *
	 * @var \Goettertz\BcVoting\Domain\Repository\BallotRepository
	 * @inject
	 */
	protected $ballotRepository = NULL;
	
	/**
	 * assetRepository
	 * 
	 * @var \Goettertz\BcVoting\Domain\Repository\AssetRepository
	 * @inject
	 */
	protected $assetRepository = NULL;

	/**
	 * action list
	 * 
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 *
	 * @return void
	 */
	public function listAction(\Goettertz\BcVoting\Domain\Model\Project $project) {
		
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$this->view->assign('user', $user);
		}
		$ballots = $this->ballotRepository->findByProject($project);
		$this->view->assign('ballots', $ballots);
		$this->view->assign('project', $project);
	}
	
	/**
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $newBallot
	 * @param string $redirect
	 */
	public function newAction(\Goettertz\BcVoting\Domain\Model\Project $project, \Goettertz\BcVoting\Domain\Model\Ballot $newBallot = NULL, $redirect = '') {
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
			If($assignment != NULL) {
				$this->view->assign('newBallot', $newBallot);
				$this->view->assign('project', $project);
				$this->view->assign('redirect', $redirect);
			}
			else {
				# msg und redirect zu listaction
				$this->addFlashMessage('You are no admin!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				$this->redirect('show','Project','BcVoting',array('project'=>$project));
			}			
		}
		else {
			$this->addFlashMessage('You aren\'t currently logged in! Please goto <a href="/login/">login</a> or <a href="/register/">register</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			$this->redirect('show','Project','BcVoting',array('project'=>$project));
		}		
	}
	
	/**
	 * Set TypeConverter option for image upload
	 */
	public function initializeCreateAction() {
		$this->setTypeConverterConfigurationForImageUpload('newBallot');
	}
	
	/**
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $newBallot
	 */
	public function createAction(\Goettertz\BcVoting\Domain\Model\Project $project, \Goettertz\BcVoting\Domain\Model\Ballot $newBallot) {
		
		if ($user = $this->userRepository->getCurrentFeUser()) {
			$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
			If($assignment != NULL) {
				$newBallot->setProject($project);
				# gets correct UNIX timestamp only if contained in formdata
				if ($newBallot->getStart() != '') {
					$start = strtotime($newBallot->getStart());
					if($start > 0) $newBallot->setStart($start);
				}
					 
				if ($newBallot->getEnd() != '') {
					$end = strtotime($newBallot->getEnd());
					if ($end > 0) $newBallot->setEnd($end);
				}
				
				$this->ballotRepository->add($newBallot);
				$this->addFlashMessage('The object was created.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
				// 			$persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager");
				// 			$persistenceManager->persistAll();
					
				// 			$ballot = $this->ballotRepository->findByUid($newBallot->getUid());
					
				$this->redirect('list','Ballot','BcVoting', array('project' => $project));
			}
			else {
				# msg und redirect zu listaction
				$this->addFlashMessage('You are no admin!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				$this->redirect('show','Project','BcVoting',array('project'=>$project));
			}
		}
		else {
			# msg und redirect zu listaction
			$this->addFlashMessage('You are not currently logged in!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			$this->redirect('show', 'Project', 'BcVoting', array('project'=>$project));
		}
	}
	
	/**
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 */
	public function showAction(\Goettertz\BcVoting\Domain\Model\Ballot $ballot) {
		$project = $ballot->getProject();
		$isAssigned = 'false';
		$isAdmin 	= 'false';
// 		$or = new \Goettertz\BcVoting\Service\OP_RETURN($project->getRpcPassword(), $project->getRpcPort());
// 		if ($opr = $or->OP_RETURN_retrieve($project->getReference())) {
// 			if ($hashBc = $opr[0]['data']) {
// 				$json = $this->projectRepository->getJson($project);
// 				$hash = $this->projectRepository->getHash($json);
// 				if ($hash !== $hashBc) {
// 					$this->addFlashMessage('Error: Ballot has changed!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
// 					$this->redirect('show','Project','BcVoting',array('project'=>$project));
// 				}
// 				else {
					if ($user = $this->userRepository->getCurrentFeUser()) {
						$username = $user->getUsername();
// 						if ($blockchain) {
// 							$amount = $blockchain->getUserBalance($username, $project);
// 						}
						if ($project) {
							$assignment = $user ? $project->getAssignmentForUser($user) : NULL;
							If($assignment != NULL) {
								$isAssigned = 'true';
								$this->view->assign('isAssigned', $isAssigned);
							}							
						}
					}
					$this->view->assign('ballot', $ballot);
					$this->view->assign('isAssigned', $isAssigned);
// 				}
// 			}
// 		}
// 		else {
// 			$this->addFlashMessage('No such reference!!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
// 		}

	}
	
	/**
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $ballot
	 */
	public function editAction(\Goettertz\BcVoting\Domain\Model\Ballot $ballot) {
		
		$project = $ballot->getProject();
		
		# Check if sealed
		if ($ballot->getReference() === '') {
			# Check FE-User
			if ($user = $this->userRepository->getCurrentFeUser()) {
			
				$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
				If($assignment != NULL) {
					
					if (!empty($project->getRpcUser())) $bcArray = Blockchain::getRpcResult($project)->listpermissions('issue');
					
					$this->view->assign('issuePermission', $bcArray[0]['address']);
					$this->view->assign('ballot', $ballot);
					$this->view->assign('assigned', true);
					$this->view->assign('admin', 'true');
				}
				else {
					# msg und redirect zu listaction
					$this->addFlashMessage('You are no admin!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
					$this->redirect('show','Project','BcVoting',array('project'=>$project));						
				}
			}
			else {
				# msg und redirect zu listaction
				$this->addFlashMessage('You are not currently logged in!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				$this->redirect('show', 'Project', 'BcVoting', array('project'=>$project));	
			}			
		}
		else {
			$this->addFlashMessage('Project is sealed!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			$this->redirect('list','Ballot','BcVoting',array('project'=>$project));
		}
	}
	
	/**
	 * action delete
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $ballot
	 * @return void
	 */	
	public function deleteAction(\Goettertz\BcVoting\Domain\Model\Ballot $ballot) {
		$project = $ballot->getProject();
		if ($ballot->getReference() === '') {
			if ($user = $this->userRepository->getCurrentFeUser()) {
				$assignment = $user ? $project->getAssignmentForUser($user,'admin') : NULL;
				If($assignment != NULL) {
					$this->addFlashMessage('The object was deleted.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
					$this->ballotRepository->remove($ballot);
					$this->redirect('edit', 'Project', NULL, array('project'=>$project));						
				}
			}
		}
	}

	/**
	 * Set TypeConverter option for image upload
	 */
	public function initializeUpdateAction() {
		$this->setTypeConverterConfigurationForImageUpload('ballot');
	}
	
	/**
	 * action update
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $ballot
	 * @return void
	 */
	public function updateAction(\Goettertz\BcVoting\Domain\Model\Ballot $ballot) {
		
		$project = $ballot->getProject();
		# Nur Update, wenn noch nicht sealed
		if ($ballot->getReference() === '') {
			
			# Nur update, wenn login
			if ($user = $this->userRepository->getCurrentFeUser()) {
			
				$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
				If($assignment != NULL) {
			
					# gets correct UNIX timestamp only if contained in formdata
					if ($ballot->getStart() != '') {
						$start = strtotime($ballot->getStart());
						if($start > 0) $ballot->setStart($start);
					}
					 
					if ($ballot->getEnd() != '') {
						$end = strtotime($ballot->getEnd());
						if ($end > 0) $ballot->setEnd($end);
					}
				
					$this->ballotRepository->update($ballot);
					$this->addFlashMessage('The ballot was updated.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
						
				}
				else {
					$this->addFlashMessage('You\'re no admin!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				}
				
			}
			else {
				$this->addFlashMessage('You\'re not logged in!!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			}
				
		}
		else {
			$this->addFlashMessage('Ballot is sealed!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			$this->redirect('edit','Project','BcVoting',array('project'=>$project));
		}
		
	$this->redirect('edit','Ballot','BcVoting',array('ballot'=>$ballot));			
	}

	/**
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $ballot
	 */
	public function removeLogoAction(\Goettertz\BcVoting\Domain\Model\Ballot $ballot) {
		$sql = 'UPDATE sys_file_reference SET deleted=1 WHERE tablenames=\'tx_bcvoting_domain_model_ballot\' AND fieldname=\'logo\' AND uid_foreign = '.$ballot->getUid().' AND deleted = 0';
		$db = $GLOBALS['TYPO3_DB']->sql_query($sql);
		$this->redirect('edit','Ballot','BcVoting',array('ballot'=>$ballot));
	}
	
	/**
	 * seals the ballot properties
	 * 
	 * after sealing the relevant properties of the project 
	 * (ballot, options, time periods) should not be changed anymore
	 * 
	 * to seal the properties a hash is stored in the blockchain and the Blocknumber, reference-id ist stored as a project property
	 * 
	 * @param \Goettertz\BcVoting\Domain\Model\Ballot $ballot
	 */
	public function sealBallotAction(\Goettertz\BcVoting\Domain\Model\Ballot $ballot) {

		$project = $ballot->getProject();

		if ($project->getRpcPassword() !== '') {
			if ($user = $this->userRepository->getCurrentFeUser()) {
			
				$assignment = $user ? $project->getAssignmentForUser($user, 'admin') : NULL;
				If($assignment != NULL) {
			
					# Check if sealed
					if ($ballot->getReference() === '') {
						$opReturnMessage = new \Goettertz\BcVoting\Service\OP_RETURN($project->getRpcPassword(), $project->getRpcPort());
							
						# The data for sealing ...
						$json = $ballot->getJson($ballot);
						$hash = $this->getHash($json);
						
						# issue asset
						$bcArray = \Goettertz\BcVoting\Service\Blockchain::getRpcResult($project)->listpermissions('issue');
						$address = $bcArray[0]['address'];
						
						$newAsset = new \Goettertz\BcVoting\Domain\Model\Asset();
						$newAsset->setName($ballot->getName());
						$newAsset->setQuantity(20000000);
						$newAsset->setDivisibility(1);

						if ($result = Blockchain::getRpcResult($project)->issue($address, $newAsset->getName(), $newAsset->getQuantity(), $newAsset->getDivisibility())) {
							
							$asset = Blockchain::getRpcResult($project)->listassets($result);
//							$newAsset->setAssetId($asset[0]['assetref']);
//							$this->assetRepository->update($newAsset);
							
							$ballot->setAsset($asset[0]['assetref']);
						}
							
						# Saving data in the blockchain ...
						if ($ref = $opReturnMessage->OP_RETURN_store($hash)  ) {
							$ballot->setReference($ref['ref']);
							$this->ballotRepository->update($ballot);
							$this->addFlashMessage('The ballot was sealed.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
							$this->view->assign('ref', $ref['ref']);
						}
						
						# Create Asset
						//$this->createAsset($ballot->getName());
						
						$this->view->assign('project', $project);
						$this->view->assign('json', $json);
						$this->view->assign('hash', $hash);
					}
					else {
						# redirect show ballot
						die('Already sealed! ('.$ballot->getReference().')');
					}
				}
				else {
					# redirect show ballot
					die('No admin!');
				}
			}
			else {
				$this->addFlashMessage('Your login is expired!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
				//die('Not currently logged in!');
			}
				
		}
		else {
			$this->addFlashMessage('No Blockchain configured!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		}
		$this->view->assign('project', $project);
	}

	/**
	 * action vote
	 *
	 * @param \Goettertz\BcVoting\Domain\Model\Option $option
	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
	 * @return void
	 */
	public function voteAction(\Goettertz\BcVoting\Domain\Model\Option $option, \Goettertz\BcVoting\Domain\Model\Project $project) {
	
		if ($user = $this->userRepository->getCurrentFeUser()) {
			
			$votings = $this->votingRepository->findByProject($project);
			$countVotings = count($votings);
			$isAssigned = false;
			$assignment = $user ? $project->getAssignmentForUser($user) : NULL;
			$votes = 0;
	
			# Wenn angemeldet
			If($assignment != NULL) {
				$username = $user->getUsername();
	
				# Nur bei Wahl ohne Blockchain und Coins
				$votes = $assignment->getVotes();
	
				# Wahl mit Blockchain ...
				if ($project->getRpcServer() !== '') {
					
					// $votes = assets
	
					# Wenn BC mit Coins
// 					$blockchain = new \Goettertz\BcVoting\Service\Blockchain();
//					if ($txid = $blockchain->getBlockchain($project)->sendfrom($username,$project->getWalletAddress(), 1)) {
						
					$bc = new \Goettertz\BcVoting\Service\OP_RETURN($project->getRpcPassword(), $project->getRpcPort());
					if ($votes >= 1) {
						$plaintext = $option->getName(); //"This string was AES-256 / CBC / ZeroBytePadding encrypted.";
							
						$mcrypt = new \Goettertz\BcVoting\Service\MCrypt();
						$secret = $mcrypt->encrypt($plaintext);
						$hash = hash('md5',$secret);
							
						if ($ref = $bc->OP_RETURN_store($hash)  ) {
							if (!is_string($ref['error']))
							{
								// Alternative $bc->OP_RETURN_send($option->getWalletAddress(), 1, $hash);
								// -> txid statt referenz
								// Stattdessen votes von assignments abziehen!!!!
								$votes = $votes - 1;
								$assignment->setVotes($votes);
	
								$this->assignmentRepository->update($assignment);
	
								$voting = new \Goettertz\BcVoting\Domain\Model\Voting();
								$voting->setReference($ref['ref']);
								$voting->setTxid($ref['txids'][0]);
								$voting->setSecret($secret);
								$voting->setProject($project);
								$this->votingRepository->add($voting);
								$this->addFlashMessage('Vote '.$project->getName().': success!<br />Ref: '.$ref['ref'].': '.$hash, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
							}
							else
							{
								$this->addFlashMessage('Vote failed: '.$ref['error'], '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
							}
						}
					}
					else {
						$this->addFlashMessage('Vote failed: Not enough votes!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
					}
				}
				else {
					$this->addFlashMessage('No RPC-Server!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
					// 					die('No RPC-Server!');
				}
			}
			else {
				$this->addFlashMessage('Not assigned!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
			}
		}
		else {
			$this->addFlashMessage('Vote failed: your login is expired!', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		}
		$this->redirect('show', 'Project', NULL, array('project' => $project, 'count' => $countVotings));
	}
	
	
	/**
	 * @param string $string
	 * @return string
	 */
	protected function getHash($string) {
		return $hash = hash('sha256', $string);
	}

	/**
	 * @param string $argumentName - object model name (lowercase)
	 */
	protected function setTypeConverterConfigurationForImageUpload($argumentName) {
		$uploadConfiguration = array(
				UploadedFileReferenceConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/tx_bc_voting/',
				UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => '2'
		);
		/** @var PropertyMappingConfiguration $newExampleConfiguration */
		$newExampleConfiguration = $this->arguments[$argumentName]->getPropertyMappingConfiguration();
		$newExampleConfiguration->forProperty('logo')
		->setTypeConverterOptions(
				'Goettertz\\BcVoting\\Property\\TypeConverter\\UploadedFileReferenceConverter',
				$uploadConfiguration
				);
	}
	
// 	/**
// 	 * create asset for ballot
// 	 * 
// 	 * @param string $name
// 	 * 
// 	 * @return \Goettertz\BcVoting\Domain\Model\Asset
// 	 */
// 	protected function createAsset($name = 'bvs') {
		
// 		$asset = new \Goettertz\BcVoting\Domain\Model\Asset();
// 		$asset->AssetDefinition($assetId, $name, $ticker, $divisibility, $iconUrl);
// 		$this->assetRepository->add($asset);
// 		return $asset;
// 	}
	
// 	/**
// 	 * getBlockchain
// 	 *
// 	 * @param \Goettertz\BcVoting\Domain\Model\Project $project
// 	 * @return \Goettertz\BcVoting\Service\jsonRPCClient $blockchain
// 	 */
// 	Protected function getBlockchain(\Goettertz\BcVoting\Domain\Model\Project $project) {
// 		$rpcServer = $project->getRpcServer();
// 		$rpcUser = $project->getRpcUser();
// 		$rpcPassword = $project->getRpcPassword();
// 		$rpcPort = $project->getRpcPort();
// 		try
// 		{
// 			if ($rpcServer!='' && $rpcUser!='' && $rpcPort!=Null && $rpcPassword != '')
// 			{
// 				return $blockchain =  new \Goettertz\BcVoting\Service\jsonRPCClient('http://'.$rpcUser.':'.$rpcPassword.'@'.$rpcServer.':'.$rpcPort.'/');
// 				// 				return $blockchain =  new \Goettertz\BcVoting\Service\jsonRPCClient('http://multichainrpc:AK2tN1i1RWT5AhDYW8ZRK5BMcNTVfMcqQFRZDgtc7Z9f@blockchain-voting.org:6332');
// 			}
// 			else
// 			{
// 				$this->addFlashMessage('Kein RPC-Server!', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
// 				return NULL;
// 			}
				
// 		}
// 		catch (Exception $e)
// 		{
// 			$this->addFlashMessage('Fehler: '.$e, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
// 			return NULL;
// 		}
// 	}
}
?>