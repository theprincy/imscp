<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace iMSCP\Authentication\Listener;

use iMSCP\Application;
use iMSCP\Authentication\AuthEvent;
use iMSCP\Authentication\AuthResult;
use iMSCP\Crypt;
use iMSCP\Functions\Daemon;
use iMSCP\Model\UserIdentity;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Hydrator\Reflection as ReflectionHydrator;

/**
 * Class CheckCredentials
 *
 * Default credentials authentication listener
 * Expects to listen on the AuthEvent::EVENT_AUTHENTICATION
 *
 * @package iMSCP\Authentication\Listener
 */
class CheckCredentials implements AuthenticationListenerInterface
{
    /**
     * @inheritdoc
     */
    public function __invoke(AuthEvent $event): void
    {
        $username = !empty($_POST['admin_name']) ? encodeIdna(cleanInput($_POST['admin_name'])) : '';
        $password = !empty($_POST['admin_pass']) ? cleanInput($_POST['admin_pass']) : '';

        if ($username === '' || $password === '') {
            $messages = [];

            if (empty($username)) {
                $message[] = tr('The username field is empty.');
            }

            if (empty($password)) {
                $message[] = tr('The password field is empty.');
            }

            $event->setAuthenticationResult(new AuthResult(
                count($messages) == 2 ? AuthResult::FAILURE : AuthResult::FAILURE_CREDENTIAL_INVALID, NULL, $messages
            ));
            return;
        }

        $stmt = Application::getInstance()->getDb()->createStatement(
            'SELECT admin_id, admin_name, admin_pass, admin_type, email, created_by FROM admin WHERE admin_name = ?'
        );
        $result = $stmt->execute([$username]);

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new HydratingResultSet(new ReflectionHydrator, new UserIdentity());
            $resultSet->initialize($result);

            if (count($resultSet) < 1) {
                $event->setAuthenticationResult(new AuthResult(AuthResult::FAILURE_IDENTITY_NOT_FOUND, NULL, [tr('Unknown username.')]));
                return;
            }

            $identity = $resultSet->current();

            if (!Crypt::verify($password, $identity->getUserPassword())) {
                $event->setAuthenticationResult(new AuthResult(AuthResult::FAILURE_CREDENTIAL_INVALID, NULL, [tr('Bad password.')]));
                return;
            }

            // If not a Bcrypt hashed password, we need recreate the hash
            if (strpos($identity->getUserPassword(), '$2a$') !== 0) {
                // We must defer password hash update to handle cases where the authentication
                // process has failed later on (case of a multi-factor authentication process)
                Application::getInstance()->getEventManager()->attach(
                    AuthEvent::EVENT_AFTER_AUTHENTICATION,
                    function (AuthEvent $event) use ($password) {
                        $authResult = $event->getAuthenticationResult();
                        if (!$authResult->isValid()) {
                            // Return early if authentication process has failed somewhere else
                            return;
                        }

                        $identity = $authResult->getIdentity();
                        $stmt = Application::getInstance()->getDb()->createStatement(
                            'UPDATE admin SET admin_pass = ?, admin_status = ? WHERE admin_id = ?'
                        );
                        $stmt->execute([Crypt::bcrypt($password), $identity->getUserType() == 'user' ? 'tochangepwd' : 'ok', $identity->getUserId()]);
                        writeLog(sprintf('Password hash for user %s has been updated using Bcrypt algorithm', $identity->getUsername()), E_USER_NOTICE);
                        $identity->getUserType() != 'user' or Daemon::sendRequest();
                    }
                );
            }

            $event->setAuthenticationResult(new AuthResult(AuthResult::SUCCESS, $identity));
        }
    }
}