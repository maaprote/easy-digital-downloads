<?php
/**
 * Import Functions
 *
 * These are functions are used for import data into Easy Digital Downloads.
 *
 * @package     EDD
 * @subpackage  Admin/Import
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Upload an import file with ajax
 *
 * @since 2.6
 * @return void
 */
function edd_do_ajax_import_file_upload() {

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	require_once EDD_PLUGIN_DIR . 'includes/admin/import/class-batch-import.php';

	if( ! wp_verify_nonce( $_REQUEST['edd_ajax_import'], 'edd_ajax_import' ) ) {
		wp_send_json_error( array( 'error' => __( 'Nonce verification failed', 'easy-digital-downloads' ) ) );
	}

	if( empty( $_POST['edd-import-class'] ) ) {
		wp_send_json_error( array( 'error' => __( 'Missing import parameters. Import class must be specified.', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	if( empty( $_FILES['edd-import-file'] ) ) {
		wp_send_json_error( array( 'error' => __( 'Missing import file. Please provide an import file.', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	$accepted_mime_types = array(
		'text/csv',
		'text/comma-separated-values',
		'text/plain',
		'text/anytext',
		'text/*',
		'text/plain',
		'text/anytext',
		'text/*',
		'application/csv',
		'application/excel',
		'application/vnd.ms-excel',
		'application/vnd.msexcel',
	);

	if( empty( $_FILES['edd-import-file']['type'] ) || ! in_array( strtolower( $_FILES['edd-import-file']['type'] ), $accepted_mime_types ) ) {
		wp_send_json_error( array( 'error' => __( 'The file you uploaded does not appear to be a CSV file.', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	if( ! file_exists( $_FILES['edd-import-file']['tmp_name'] ) ) {
		wp_send_json_error( array( 'error' => __( 'Something went wrong during the upload process, please try again.', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	// Let WordPress import the file. We will remove it after import is complete
	$import_file = wp_handle_upload( $_FILES['edd-import-file'], array( 'test_form' => false ) );

	if ( $import_file && empty( $import_file['error'] ) ) {

		do_action( 'edd_batch_import_class_include', $_POST['edd-import-class'] );

		$import = new $_POST['edd-import-class']( $import_file['file'] );

		if( ! $import->can_import() ) {
			wp_send_json_error( array( 'error' => __( 'You do not have permission to import data', 'easy-digital-downloads' ) ) );
		}

		wp_send_json_success( array(
			'form'      => $_POST,
			'class'     => $_POST['edd-import-class'],
			'upload'    => $import_file,
			'first_row' => $import->get_first_row(),
			'columns'   => $import->get_columns(),
			'nonce'     => wp_create_nonce( 'edd_ajax_import', 'edd_ajax_import' )
		) );

	} else {

		/**
		 * Error generated by _wp_handle_upload()
		 * @see _wp_handle_upload() in wp-admin/includes/file.php
		 */

		wp_send_json_error( array( 'error' => $import_file['error'] ) );
	}

	exit;

}
add_action( 'edd_upload_import_file', 'edd_do_ajax_import_file_upload' );

/**
 * Process batch imports via ajax
 *
 * @since 2.6
 * @return void
 */
function edd_do_ajax_import() {

	require_once EDD_PLUGIN_DIR . 'includes/admin/import/class-batch-import.php';

	if( ! wp_verify_nonce( $_REQUEST['nonce'], 'edd_ajax_import' ) ) {
		wp_send_json_error( array( 'error' => __( 'Nonce verification failed', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	if( empty( $_REQUEST['class'] ) ) {
		wp_send_json_error( array( 'error' => __( 'Missing import parameters. Import class must be specified.', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	if( ! file_exists( $_REQUEST['upload']['file'] ) ) {
		wp_send_json_error( array( 'error' => __( 'Something went wrong during the upload process, please try again.', 'easy-digital-downloads' ), 'request' => $_REQUEST ) );
	}

	do_action( 'edd_batch_import_class_include', $_REQUEST['class'] );

	$step     = absint( $_REQUEST['step'] );
	$class    = $_REQUEST['class'];
	$import   = new $class( $_REQUEST['upload']['file'], $step );

	if( ! $import->can_import() ) {

		wp_send_json_error( array( 'error' => __( 'You do not have permission to import data', 'easy-digital-downloads' ) ) );

	}

	parse_str( $_REQUEST['mapping'], $map );

	$import->map_fields( $map['edd-import-field'] );

	$ret = $import->process_step( $step );

	$percentage = $import->get_percentage_complete();

	if( $ret ) {

		$step += 1;
		wp_send_json_success( array(
			'step'       => $step,
			'percentage' => $percentage,
			'columns'    => $import->get_columns(),
			'mapping'    => $import->field_mapping,
			'total'      => $import->total
		) );

	} elseif ( true === $import->is_empty ) {

		wp_send_json_error( array(
			'error' => __( 'No data found for import parameters', 'easy-digital-downloads' )
		) );

	} else {

		wp_send_json_success( array(
			'step'    => 'done',
			'message' => sprintf(
				__( 'Import complete! <a href="%s">View imported %s</a>.', 'easy-digital-downloads' ),
				esc_url( $import->get_list_table_url() ),
				$import->get_import_type_label()
			)
		) );

	}
}
add_action( 'wp_ajax_edd_do_ajax_import', 'edd_do_ajax_import' );
