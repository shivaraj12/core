<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Lock;

use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Test\TestCase;

abstract class LockingProvider extends TestCase {
	/**
	 * @var \OCP\Lock\ILockingProvider
	 */
	protected $instance1;
	/**
	 * @var \OCP\Lock\ILockingProvider
	 */
	protected $instance2;

	/**
	 * @return \OCP\Lock\ILockingProvider
	 */
	abstract protected function getInstance();

	protected function setUp() {
		parent::setUp();
		$this->instance1 = $this->getInstance();
		$this->instance2 = $this->getInstance();
	}

	public function testExclusiveLock() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testSharedLock() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testDoubleSharedLock() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testReleaseSharedLock() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));

		$this->instance1->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testDoubleExclusiveLock() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->instance2->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	public function testReleaseExclusiveLock() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->instance1->releaseLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testExclusiveLockAfterShared() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));

		$this->instance2->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	public function testExclusiveLockAfterSharedReleased() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testReleaseAll() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance1->acquireLock('bar', ILockingProvider::LOCK_SHARED);
		$this->instance1->acquireLock('asd', ILockingProvider::LOCK_EXCLUSIVE);

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertTrue($this->instance1->isLockOwned('bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('bar', ILockingProvider::LOCK_SHARED));
		$this->assertTrue($this->instance1->isLockOwned('asd', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('asd', ILockingProvider::LOCK_EXCLUSIVE));

		$this->instance1->releaseAll();

		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLocked('bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLocked('asd', ILockingProvider::LOCK_EXCLUSIVE));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance1->isLockOwned('bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance1->isLockOwned('asd', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('asd', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testReleaseAfterReleaseAll() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));

		$this->instance1->releaseAll();

		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));

		$this->instance1->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}


	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testSharedLockAfterExclusive() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->instance2->acquireLock('foo', ILockingProvider::LOCK_SHARED);
	}

	public function testLockedExceptionHasPathForShared() {
		try {
			$this->testSharedLockAfterExclusive();
			$this->fail('Expected locked exception');
		} catch (LockedException $e) {
			$this->assertEquals('foo', $e->getPath());
		}
	}

	public function testLockedExceptionHasPathForExclusive() {
		try {
			$this->testExclusiveLockAfterShared();
			$this->fail('Expected locked exception');
		} catch (LockedException $e) {
			$this->assertEquals('foo', $e->getPath());
		}
	}

	public function testChangeLockToExclusive() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));

		$this->instance1->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testChangeLockToShared() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));

		$this->instance1->changeLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance2->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance2->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance2->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testChangeLockToExclusiveDoubleShared() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testChangeLockToExclusiveNoShared() {
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testChangeLockToExclusiveFromExclusive() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->instance1->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testChangeLockToSharedNoExclusive() {
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->changeLock('foo', ILockingProvider::LOCK_SHARED);
	}

	/**
	 * @expectedException \OCP\Lock\LockedException
	 */
	public function testChangeLockToSharedFromShared() {
		$this->instance1->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance1->isLockOwned('foo', ILockingProvider::LOCK_SHARED));
		$this->instance1->changeLock('foo', ILockingProvider::LOCK_SHARED);
	}
}
