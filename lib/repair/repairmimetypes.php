<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2014 Jörn Dreyer jfd@owncloud.com
 * Copyright (c) 2014 Olivier Paroz owncloud@oparoz.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;

class RepairMimeTypes extends BasicEmitter implements \OC\RepairStep {

	public function getName() {
		return 'Repair mime types';
	}

	private static function existsStmt() {
		return \OC_DB::prepare('
			SELECT count(`mimetype`)
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');
	}

	private static function getIdStmt() {
		return \OC_DB::prepare('
			SELECT `id`
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');
	}

	private static function insertStmt() {
		return \OC_DB::prepare('
			INSERT INTO `*PREFIX*mimetypes` ( `mimetype` )
			VALUES ( ? )
		');
	}

	private static function updateWrongStmt() {
		return \OC_DB::prepare('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = (
				SELECT `id`
				FROM `*PREFIX*mimetypes`
				WHERE `mimetype` = ?
			) WHERE `mimetype` = ?
		');
	}

	private static function deleteStmt() {
		return \OC_DB::prepare('
			DELETE FROM `*PREFIX*mimetypes`
			WHERE `id` = ?
		');
	}

	private static function updateByNameStmt() {
		return \OC_DB::prepare('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = (
				SELECT `id`
				FROM `*PREFIX*mimetypes`
				WHERE `mimetype` = ?
			) WHERE `name` ILIKE ?
		');
	}

	private function repairMimetypes($wrongMimetypes) {
		foreach ($wrongMimetypes as $wrong => $correct) {
			// do we need to remove a wrong mimetype?
			$result = \OC_DB::executeAudited(self::getIdStmt(), array($wrong));
			$wrongId = $result->fetchOne();

			if ($wrongId !== false) {
				// do we need to insert the correct mimetype?
				$result = \OC_DB::executeAudited(self::existsStmt(), array($correct));
				$exists = $result->fetchOne();

				if (!is_null($correct)) {
					if (!$exists) {
						// insert mimetype
						\OC_DB::executeAudited(self::insertStmt(), array($correct));
					}

					// change wrong mimetype to correct mimetype in filecache
					\OC_DB::executeAudited(self::updateWrongStmt(), array($correct, $wrongId));
				}

				// delete wrong mimetype
				\OC_DB::executeAudited(self::deleteStmt(), array($wrongId));

			}
		}
	}

	private function updateMimetypes($updatedMimetypes) {

		foreach ($updatedMimetypes as $extension => $mimetype) {
			$result = \OC_DB::executeAudited(self::existsStmt(), array($mimetype));
			$exists = $result->fetchOne();

			if (!$exists) {
				// insert mimetype
				\OC_DB::executeAudited(self::insertStmt(), array($mimetype));
			}

			// change mimetype for files with x extension
			\OC_DB::executeAudited(self::updateByNameStmt(), array($mimetype, '%.' . $extension));
		}
	}

	private function fixOfficeMimeTypes() {
		// update wrong mimetypes
		$wrongMimetypes = array(
			'application/mspowerpoint' => 'application/vnd.ms-powerpoint',
			'application/msexcel' => 'application/vnd.ms-excel',
		);

		self::repairMimetypes($wrongMimetypes);

		$updatedMimetypes = array(
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		);


		// separate doc from docx etc
		self::updateMimetypes($updatedMimetypes);

	}

	private function fixApkMimeType() {
		$updatedMimetypes = array(
			'apk' => 'application/vnd.android.package-archive',
		);

		self::updateMimetypes($updatedMimetypes);
	}

	private function fixFontsMimeTypes() {
		// update wrong mimetypes
		$wrongMimetypes = array(
			'font' => null,
			'font/opentype' => 'application/font-sfnt',
			'application/x-font-ttf' => 'application/font-sfnt',
		);

		self::repairMimetypes($wrongMimetypes);

		$updatedMimetypes = array(
			'ttf' => 'application/font-sfnt',
			'otf' => 'application/font-sfnt',
			'pfb' => 'application/x-font',
		);

		self::updateMimetypes($updatedMimetypes);
	}

	private function fixPostscriptMimeType() {
		$updatedMimetypes = array(
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
		);

		self::updateMimetypes($updatedMimetypes);
	}

	private function introduceRawMimeType() {
		$updatedMimetypes = array(
			'arw' => 'image/x-dcraw',
			'cr2' => 'image/x-dcraw',
			'dcr' => 'image/x-dcraw',
			'dng' => 'image/x-dcraw',
			'erf' => 'image/x-dcraw',
			'iiq' => 'image/x-dcraw',
			'k25' => 'image/x-dcraw',
			'kdc' => 'image/x-dcraw',
			'mef' => 'image/x-dcraw',
			'nef' => 'image/x-dcraw',
			'orf' => 'image/x-dcraw',
			'pef' => 'image/x-dcraw',
			'raf' => 'image/x-dcraw',
			'rw2' => 'image/x-dcraw',
			'srf' => 'image/x-dcraw',
			'sr2' => 'image/x-dcraw',
			'xrf' => 'image/x-dcraw',
		);

		self::updateMimetypes($updatedMimetypes);
	}

	private function introduce3dImagesMimeType() {
		$updatedMimetypes = array(
			'jps' => 'image/jpeg',
			'mpo' => 'image/jpeg',
		);

		self::updateMimetypes($updatedMimetypes);
	}

	/**
	 * Fix mime types
	 */
	public function run() {
		if ($this->fixOfficeMimeTypes()) {
			$this->emit('\OC\Repair', 'info', array('Fixed office mime types'));
		}

		if ($this->fixApkMimeType()) {
			$this->emit('\OC\Repair', 'info', array('Fixed APK mime type'));
		}

		if ($this->fixFontsMimeTypes()) {
			$this->emit('\OC\Repair', 'info', array('Fixed fonts mime types'));
		}

		if ($this->fixPostscriptMimeType()) {
			$this->emit('\OC\Repair', 'info', array('Fixed Postscript mime types'));
		}

		if ($this->introduceRawMimeType()) {
			$this->emit('\OC\Repair', 'info', array('Fixed Raw mime types'));
		}

		if ($this->introduce3dImagesMimeType()) {
			$this->emit('\OC\Repair', 'info', array('Fixed 3D images mime types'));
		}
	}
}
