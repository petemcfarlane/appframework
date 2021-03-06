<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\AppFramework\Db;

use OCA\AppFramework\Core\API;
use OCA\AppFramework\Utility\MapperTestUtility;


require_once(__DIR__ . "/../classloader.php");


class Example extends Entity {
	public $preName;
	public $email;
};


class ExampleMapper extends Mapper {
	public function __construct(API $api){ parent::__construct($api, 'table'); }
	public function find($table, $id){ return $this->findOneQuery($table, $id); }
	public function findOneEntity($table, $id){ return $this->findEntity($table, $id); }
	public function findAll($table){ return $this->findAllQuery($table); }
	public function findAllEntities($table){ return $this->findEntities($table); }
	public function mapRow($row){ return $this->mapRowToEntity($row); }
	public function pDeleteQuery($table, $id){ $this->deleteQuery($table, $id); }
}


class MapperTest extends MapperTestUtility {

	private $mapper;

	public function setUp(){
		$this->beforeEach();
		$this->mapper = new ExampleMapper($this->api);
	}


	public function testMapperShouldSetTableName(){
		$this->assertEquals('*PREFIX*table', $this->mapper->getTableName());
	}


	public function testFindQuery(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('hi')
		);
		$row = $this->setMapperResult($sql, $params, $rows);		
		$this->mapper->find($sql, $params);
	}

	public function testFindEntity(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('hi')
		);
		$row = $this->setMapperResult($sql, $params, $rows);
		$this->mapper->findOneEntity($sql, $params);
	}

	public function testFindNotFound(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array();
		$row = $this->setMapperResult($sql, $params, $rows);		
		$this->setExpectedException(
			'\OCA\AppFramework\Db\DoesNotExistException');
		$this->mapper->find($sql, $params);
	}

	public function testFindEntityNotFound(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array();
		$row = $this->setMapperResult($sql, $params, $rows);
		$this->setExpectedException(
			'\OCA\AppFramework\Db\DoesNotExistException');
		$this->mapper->findOneEntity($sql, $params);
	}

	public function testFindMultiple(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('jo'), array('ho')
		);
		$row = $this->setMapperResult($sql, $params, $rows);
		$this->setExpectedException(
			'\OCA\AppFramework\Db\MultipleObjectsReturnedException');
		$this->mapper->find($sql, $params);
	}

	public function testFindEntityMultiple(){
		$sql = 'hi';
		$params = array('jo');
		$rows = array(
			array('jo'), array('ho')
		);
		$row = $this->setMapperResult($sql, $params, $rows);
		$this->setExpectedException(
			'\OCA\AppFramework\Db\MultipleObjectsReturnedException');
		$this->mapper->findOneEntity($sql, $params);
	}


	public function testDelete(){
		$sql = 'DELETE FROM `*PREFIX*table` WHERE `id` = ?';
		$params = array(2);

		$this->setMapperResult($sql, $params);
		$entity = new Example();
		$entity->setId($params[0]);

		$this->mapper->delete($entity);
	}


	public function testCreate(){
		$this->api->expects($this->once())
			->method('getInsertId')
			->with($this->equalTo('*PREFIX*table'))
			->will($this->returnValue(3));
		$this->mapper = new ExampleMapper($this->api);

		$sql = 'INSERT INTO `*PREFIX*table`(`pre_name`,`email`) ' .
				'VALUES(?,?)';
		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);

		$this->setMapperResult($sql, $params);

		$this->mapper->insert($entity);
	}


	public function testCreateShouldReturnItemWithCorrectInsertId(){
		$this->api->expects($this->once())
			->method('getInsertId')
			->with($this->equalTo('*PREFIX*table'))
			->will($this->returnValue(3));
		$this->mapper = new ExampleMapper($this->api);

		$sql = 'INSERT INTO `*PREFIX*table`(`pre_name`,`email`) ' .
				'VALUES(?,?)';
		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);

		$this->setMapperResult($sql, $params);

		$result = $this->mapper->insert($entity);

		$this->assertEquals(3, $result->getId());
	}


	public function testUpdate(){
		$sql = 'UPDATE `*PREFIX*table` ' .
				'SET ' .
				'`pre_name` = ?,'.
				'`email` = ? ' .
				'WHERE `id` = ?';

		$params = array('john', 'my@email', 1);
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);
		$entity->setId($params[2]);

		$this->setMapperResult($sql, $params);

		$this->mapper->update($entity);
	}


	public function testUpdateNoId(){
		$sql = 'UPDATE `*PREFIX*table` ' .
				'SET ' .
				'`pre_name` = ?,'.
				'`email` = ? ' .
				'WHERE `id` = ?';

		$params = array('john', 'my@email');
		$entity = new Example();
		$entity->setPreName($params[0]);
		$entity->setEmail($params[1]);

		$this->setExpectedException('InvalidArgumentException');

		$this->mapper->update($entity);
	}


	public function testMapRowToEntity(){
		$entity1 = $this->mapper->mapRow(array('pre_name' => 'test1', 'email' => 'test2'));
		$entity2 = new Example();
		$entity2->setPreName('test1');
		$entity2->setEmail('test2');
		$entity2->resetUpdatedFields();
		$this->assertEquals($entity2, $entity1);
	}

	public function testFindEntities(){
		$sql = 'hi';
		$rows = array(
			array('pre_name' => 'hi')
		);
		$entity = new Example();
		$entity->setPreName('hi');
		$entity->resetUpdatedFields();
		$row = $this->setMapperResult($sql, array(), $rows);
		$result = $this->mapper->findAllEntities($sql);
		$this->assertEquals(array($entity), $result);
	}

	public function testFindEntitiesNotFound(){
		$sql = 'hi';
		$rows = array();
		$row = $this->setMapperResult($sql, array(), $rows);
		$result = $this->mapper->findAllEntities($sql);
		$this->assertEquals(array(), $result);
	}

	public function testFindEntitiesMultiple(){
		$sql = 'hi';
		$rows = array(
			array('pre_name' => 'jo'), array('email' => 'ho')
		);
		$entity1 = new Example();
		$entity1->setPreName('jo');
		$entity1->resetUpdatedFields();
		$entity2 = new Example();
		$entity2->setEmail('ho');
		$entity2->resetUpdatedFields();
		$row = $this->setMapperResult($sql, array(), $rows);
		$result = $this->mapper->findAllEntities($sql);
		$this->assertEquals(array($entity1, $entity2), $result);
	}
}