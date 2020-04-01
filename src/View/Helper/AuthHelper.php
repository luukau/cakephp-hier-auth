<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace HierAuth\View\Helper;

use Cake\View\Helper;
use Cake\Core\Exception\Exception;
use Cake\Utility\Inflector;

/**
 * AuthHelper - Helper class that amongst other things, checks a user's access 
 * to the roles defined within the app. This allows for neat checks in the 
 * views. A view can contain checks along the lines of:
 * 
 * <?php if( $this->Auth->userHasRoleManagerOrAdmin() ): ?>
 *      ...
 * <?php endif; ?>
 * 
 * or like,
 *  
 * <?php if( $this->Auth->userHasRoleAdmin() ): ?>
 *      ...show admin stuff
 * <?php endif; ?>
 * 
 * Note that the role names are as per your definition in the hierachy.yml file.
 *  
 * @author Luuk Honings
 */
class AuthHelper extends Helper {

    public function __construct (\Cake\View\View $view, array $config = []) {

        parent::__construct($view, $config);
    }
    
    
    /** user 
     * 
     * @param none
     * @return array|null containing general user info
     */
    public function user() {
        $user = $this->getView()->getRequest()->getSession()->read('Auth.User');
        if ( isset ($user) ) {
            return $user;
        }
        return null;
    }

    /** userHasRole method
     *
     * @param array $allowedRoles array containing roles to check against.
     * @return bool true if any or the allowed roles are matched, false otherwise
     */
    public function userHasRole ($allowedRoles = array(), $minRoleCount = 1) {
        $user = $this->user();
        if ( $user ) {
            $grantedRoles = json_decode($user['roles']); 
            if (isset($grantedRoles) ) {
                return (count(array_intersect($grantedRoles, $allowedRoles)) >= $minRoleCount );
            }
        }
        return false;
    }
    
    /** __call magic method allows for 'userHasRoleSaasAdmin' or 
     *  'userHasRoleRoleoneOrRoletwo' etc etc...
     *
     * @param string $method name of method call as above
     * @param array $args array of arguments to pass, if any
     * @return returns the value of the function called (ie. `userHasRole`).
     */
    public function __call($method, $args)
    {
        $method = Inflector::underscore($method);
        preg_match('/^user_has_role_([\w]+)$/', $method, $matches);
        if (!empty($matches)) {
            // user_has_role_ is 14 characters.
            $role = strtoupper(substr($method, 14));
            $hasOr = strpos($role, '_OR_');
            $hasAnd = strpos($role, '_AND_');

            $minCount = 1;
            if ($hasOr === false && $hasAnd === false) {
                $roles = [0 => $role];
            } elseif ($hasOr !== false) {
                $roles = explode('_OR_', $role);
            } elseif ($hasAnd !== false) {
                $roles = explode('_AND_', $role);
                $minCount = 2;
            }
            
            $newArgs = [];
            array_unshift($newArgs, $minCount );
            array_unshift($newArgs, $roles );
            return call_user_func_array (array ($this,'userHasRole'), $newArgs);
        }
        return false;
    }
}
