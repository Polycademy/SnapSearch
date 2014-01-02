<?php defined('BASEPATH') OR exit('No direct script access allowed');

use PolyAuth\Storage\MySQLAdapter;
use PolyAuth\Options;
use RBAC\Permission;
use RBAC\Role\Role;
use RBAC\Manager\RoleManager;

/**
 * This migration file is catered towards Codeigniter 3.0 and the MySQL database.
 * However you can glean information from here on how to implement it in other frameworks and other databases.
 * 
 * You will need to modify the configuration array to setup the default permissions and the default user.
 * You can also add to the columns of the user_accounts table, or even change the name, just make sure to configure the name properly.
 * Any added columns will simply be extra data that you can submit when registering or getting a user.
 * If table names are changed, make sure to change them in the options too.
 * Codeigniter 3.0 sets all fields by default to null
 *
 * Of course you can edit the roles and permissions later by constructing your own back end interface, or you can programmatically do it
 *
 * The RBAC is at NIST Level 1, so the user and role land is flat, no hierarchy yet.
 */
class Migration_add_polyauth extends CI_Migration {

	public function up(){
	
		// * apiLimit is the total limit of how many times the api can be accessed
		// * apiFreeLimit is the limit that is subtracted from the apiUsage when you're about the charge the amount
		// * apiUsage is the number of usages of the API racked up in the chargeInterval, this number will subtract apiFreeLimit and if the number is positive, this is the number that will be multiplied by the charge amount (and add any apiLeftOverCharge) and be charged to the user via the payment gateway, the charge amount will be specified by the handler
		// * apiPreviousLimit was the previous limit 
		// * apiLeftOverCharge will be charge of API usages that were not charged for from the previous chargeInterval due to charge errors, this is an added amount based on cents. It assumes only ONE currency. The reason we do it this way, is so that the monthly charges can actually add up properly instead of adding up the usage which could decrease the price since the more usage, the less charge per usage.
		// * chargeInterval is ISO 8601 duration
		// * chargeDate is the next date to be charged for

		$default_user = array(
			'id'					=> '1',
			'ipAddress'				=> inet_pton('127.0.0.1'),
			'username'				=> 'administrator',
			'password'				=> '$2y$10$EiqipvSt3lnD//nchj4u9OgOTL9R3J4AbZ5bUVVrh.Tq/gmc5xIvS', //default is "password"
			'passwordChange'		=> '0',
			'email'					=> 'admin@admin.com',
			'createdOn'				=> date('Y-m-d H:i:s'),
			'lastLogin'				=> date('Y-m-d H:i:s'),
			'active'				=> '1',
			'apiLimit'				=> 100000000,
			'apiPreviousLimit'		=> 0,		
			'apiFreeLimit'			=> 100000000,
			'apiUsage'				=> 0,
			'apiLeftOverCharge'		=> 0,
			'chargeInterval'		=> 'P30D',
		);

		//30 days is more accurate than 1 month
		$charge_date = new DateTime($default_user['createdOn']);
		$charge_date->add(new DateInterval($default_user['chargeInterval']));
		$charge_date = $charge_date->format('Y-m-d H:i:s');
		$default_user['chargeDate'] = $charge_date;

		//roles to descriptions
		$default_roles = array(
			'admin'		=> 'Site Administrators',
			'member'	=> 'General Members',
		);
		
		//roles to permissions to permission descriptions
		$default_role_permissions = array(
			'admin'		=> array(
				'admin_create'	=> 'Creating administration resources.',
				'admin_read'	=> 'Viewing administration resources.',
				'admin_update'	=> 'Editing administration resources.',
				'admin_delete'	=> 'Deleting administration resources.',
			),
			'member'	=> array(
				'public_read'	=> 'Viewing public resources.',
			),
		);
		
		//default user to roles
		$default_user_roles = array(
			$default_user['id']	=> array(
				'admin',
				'member',
			),
		);

		//autoCode is for autologin
		//accessTokens would be in a separate table representing the accessTokens
		//hmac is for HawkStrategy, it's a shared secret to be generated at random
		
		// Table structure for table 'user_accounts'
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			),
			'ipAddress' => array(
				'type' => 'VARBINARY',
				'constraint' => '16'
			),
			'username' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
			),
			'password' => array(
				'type' => 'VARCHAR',
				'constraint' => '255',
			),
			'passwordChange' => array(
				'type' => 'TINYINT',
				'constraint' => '1',
				'unsigned' => TRUE,
				'default' => 0,
			),
			'email' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
			),
			'activationCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '40',
			),
			'forgottenCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '40',
			),
			'forgottenDate' => array(
				'type' => 'DATETIME',
			),
			'autoCode' => array(
				'type' => 'VARCHAR',
				'constraint' => '40',
			),
			'autoDate' => array(
				'type' => 'DATETIME',
			),
			'createdOn' => array(
				'type' => 'DATETIME',
			),
			'lastLogin' => array(
				'type' => 'DATETIME',
			),
			'active' => array(
				'type' => 'TINYINT',
				'constraint' => '1',
				'unsigned' => TRUE,
				'default' => 0,
			),
			'banned' => array(
				'type' => 'TINYINT',
				'constraint' => '1',
				'unsigned' => TRUE,
				'default' => 0,
			),
			'sharedKey' => array(
				'type' => 'TEXT'
			),
			'apiLimit'	=> [
				'type'		=> 'INT',
				'default'	=> 0,
				'unsigned' => TRUE,
			],
			'apiPreviousLimit'	[
				'type'		=> 'INT',
				'default'	=> 0,
				'unsigned' => TRUE,
			],
			'apiFreeLimit'	=> [
				'type'		=> 'INT',
				'default'	=> 0,
				'unsigned' => TRUE,
			],
			'apiUsage'	=> [
				'type'		=> 'INT',
				'default'	=> 0,
				'unsigned' => TRUE,
			],
			'apiLeftOverCharge'	=> [
				'type'		=> 'INT',
				'default'	=> 0,
				'unsigned' => TRUE,
			],
			'chargeInterval'	=> [
				'type'		=> 'TEXT',
			],
			'chargeDate'	=> [
				'type'		=> 'DATETIME'
			],
		));
		
		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->create_table('user_accounts', true);
		
		// Dumping data for table 'users'
		$this->db->insert('user_accounts', $default_user);
		
		// Table structure for table 'login_attempts'
		$this->dbforge->add_field(array(
			'id' => array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			),
			'ipAddress' => array(
				'type' => 'VARBINARY',
				'constraint' => '16',
			),
			'identity' => array(
				'type' => 'VARCHAR',
				'constraint' => '100',
			),
			'lastAttempt' => array(
				'type' => 'DATETIME',
			)
		));
		
		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->create_table('login_attempts', true);

		// Table structure for table 'external_providers' (essentially the token storage stable)
		$this->dbforge->add_field(array(
			'id'	=> array(
				'type' => 'INT',
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			),
			'userId'	=> array(
				'type' => 'INT',
				'unsigned' => TRUE,
			),
			'provider'	=> array(
				'type' => 'VARCHAR',
				'constraint' => '100',
			),
			'externalIdentifier'	=> array(
				'type'	=> 'TEXT'
			),
			'tokenObject'	=> array(
				'type'		=> 'TEXT',
			),
		));

		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->create_table('external_providers', true);
	
		//This is the RBAC schema designed for MySQL, it's complex, so we use direct queries!
		//This is LEVEL 1 RBAC, later on you can update to LEVEL 2 RBAC
		
		$create_auth_permission = 
			'CREATE TABLE `auth_permission` (
				`permission_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`name`          VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				`description`   TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
				`added_on`      DATETIME NULL DEFAULT NULL,
				`updated_on`    DATETIME NULL DEFAULT NULL,
				PRIMARY KEY (`permission_id`),
				UNIQUE INDEX `uniq_perm` USING BTREE (`name`)
			) ENGINE = InnoDB;';
		
		$this->db->query($create_auth_permission);
		
		$create_auth_role = 
			'CREATE TABLE `auth_role` (
				`role_id`     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`name`        VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				`description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
				`added_on`    DATETIME NULL DEFAULT NULL,
				`updated_on`  DATETIME NULL DEFAULT NULL,
				PRIMARY KEY (`role_id`),
				UNIQUE INDEX `uniq_name` USING BTREE (`name`)
			) ENGINE = InnoDB;';
			
		$this->db->query($create_auth_role);
		
		$create_auth_role_permissions = 
			'CREATE TABLE `auth_role_permissions` (
				`role_permission_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`role_id`            INT(10) UNSIGNED NOT NULL,
				`permission_id`      INT(10) UNSIGNED NOT NULL,
				`added_on`           DATETIME NULL DEFAULT NULL,
				PRIMARY KEY (`role_permission_id`),
				FOREIGN KEY (`permission_id`) REFERENCES `auth_permission` (`permission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (`role_id`) REFERENCES `auth_role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX `fk_role` USING BTREE (`role_id`),
				INDEX `fk_permission` USING BTREE (`permission_id`)
			)
			ENGINE = InnoDB;';
		
		$this->db->query($create_auth_role_permissions);
		
		$create_auth_subject_role = 
			'CREATE TABLE `auth_subject_role` (
				`subject_role_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`subject_id`      INT(10) UNSIGNED NOT NULL,
				`role_id`         INT(10) UNSIGNED NOT NULL,
				PRIMARY KEY (`subject_role_id`),
				FOREIGN KEY (`role_id`) REFERENCES `auth_role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE,
				UNIQUE INDEX `role_id` USING BTREE (`role_id`, `subject_id`),
				INDEX `fk_subjectid` USING BTREE (`subject_id`),
				INDEX `fk_roleid` USING BTREE (`role_id`)
			)
			ENGINE = InnoDB;';
		
		$this->db->query($create_auth_subject_role);
		
		//time to insert the default permission and role data
		$role_manager = new RoleManager(new MySQLAdapter($this->db->conn_id, new Options));
		
		foreach($default_role_permissions as $role => $permissions_array){
		
			//create the role
			$created_role = Role::create($role, $default_roles[$role]);
			
			foreach($permissions_array as $permission => $reason){

				//create the permission
				$created_permission = Permission::create($permission, $reason);
				//save the permission to the database
				$role_manager->permissionSave($created_permission);
				//add the permission to the role
				$created_role->addPermission($created_permission);
				
			}
			
			$role_manager->roleSave($created_role);
			
		}
		
		//assign the role to the default user
		foreach($default_user_roles as $user => $roles){
		
			foreach($roles as $role){
			
				$assignable_role = $role_manager->roleFetchByName($role);
				
				$role_manager->roleAddSubjectId($assignable_role, $user);
			
			}
		
		}
		
	}

	public function down(){
	
		$this->dbforge->drop_table('user_accounts');
		$this->dbforge->drop_table('login_attempts');
		//when using foreign keys, if you need to drop them, make sure to ignore them and then set them up again
		$this->db->query('SET foreign_key_checks = 0;');
		$this->dbforge->drop_table('auth_permission');
		$this->dbforge->drop_table('auth_role');
		$this->dbforge->drop_table('auth_role_permissions');
		$this->dbforge->drop_table('auth_subject_role');
		$this->db->query('SET foreign_key_checks = 1;');
	
	}
	
}