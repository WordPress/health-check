<?php

class Health_Check_Tools_Test extends WP_UnitTestCase {

	private $test_files_integrity;
	private $core_checksums;

	public function setUp() {
		parent::setUp();

		$this->test_files_integrity = new Health_Check_Files_Integrity();

		$this->core_checksums = Health_Check_Files_Integrity::call_checksum_api();
	}

	public function testFilesIntegrityUntampered() {
		$files = Health_Check_Files_Integrity::parse_checksum_results( $this->core_checksums );

		$this->assertEmpty( $files );
	}

	public function testFilesIntegrityMissingFiles() {
		$original_file = trailingslashit( ABSPATH ) . 'xmlrpc.php';
		$renamed_file  = trailingslashit( ABSPATH ) . 'xmlrpc-moved.php';

		rename( $original_file, $renamed_file );

		$files = Health_Check_Files_Integrity::parse_checksum_results( $this->core_checksums );

		$this->assertEquals( array(
			array(
				$original_file => 'File not found'
			)
		), $files );
	}
}