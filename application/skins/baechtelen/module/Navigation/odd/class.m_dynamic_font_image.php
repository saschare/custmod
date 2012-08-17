<?php
class m_dynamic_font_image {
	var $arr_image_params = array();
	var $arr_images = array();

	/**
	* _construct:
	* sets the image type by default to png because no other extension is supported yet
	* sets margin-y to auto-calculate
	* sets the line-spacing to default (0)
	* sets the default width of the image (will be calculated)
	* sets the X and Y margin
	* @param
	* @return void
	* @access public
	*/
	function m_dynamic_font_image() {
		$this->set_image_type();
		$this->set_use_automatic_marginY();
		$this->set_line_spacing();
		$this->set_image_width();
		$this->set_marginX();
		$this->set_marginY();
	}

	/**
	* sets the the text of the image
	* if the optional flag is set to 1 (default) the filename will be created with the given text and additional parameters
	* if the flag is set to 0 you have to specify the basename using the method set_basename()
	* @param $text string, $basename_from_text integer
	* @return void
	* @access public
	*/
	function set_text( $text, $basename_from_text = 1 ) {
		$this->arr_image_params['text'] = trim( $text );
		if ( $basename_from_text == 1 ) {
			$this->set_basename(str_replace( '%', '', urlencode($this->arr_image_params['text'])));
			}
		}

	/**
	* sets the hexadecimal color code of the text (i.e. 000000)
	* @param $text_color int
	* @return void
	* @access public
	*/
	function set_text_color( $text_color ) {
		$this->arr_image_params['text_color'] = trim( $text_color );
		}

	/**
	* sets the hexadecimal background color code of the image (i.e. FFFFFF)
	* @param $background_color int
	* @return void
	* @access public
	*/
	function set_background_color( $background_color ) {
		$this->arr_image_params['background_color'] = trim( $background_color );
		}

	/**
	* sets the horizontal margin around the text-box
	* @param $marginX int
	* @return void
	* @access public
	*/
	function set_marginX( $marginX = 0) {
		$this->arr_image_params['marginX'] = sprintf( '%d', trim( $marginX ) );
		}

	/**
	* sets the vertical margin offset of the text (x units from top)
	* the higher the number, the deeper gets the text set
	* @param $marginY int
	* @return void
	* @access public
	*/
	function set_marginY( $marginY = 0) {
		$this->set_use_automatic_marginY(0);
		$this->arr_image_params['marginY'] = sprintf( '%d', trim( $marginY ) ) + $this->arr_image_params['fontsize'];
		}

	/**
	* sets the line spacing of the text (default is 0)
	* @param $line_spacing int
	* @return void
	* @access public
	*/
	function set_line_spacing( $line_spacing = 0 ) {
		$this->arr_image_params['line_spacing'] = sprintf( '%d', trim( $line_spacing ) );
		}

	/**
	* sets the height of the entire image (default is 1)
	* @param $image_height int
	* @return void
	* @access public
	*/
	function set_image_height($image_height = 1 ) {
		$this->arr_image_params['image_height'] = sprintf( '%d', trim(  $image_height ) );
		}

	/**
	* sets the width of the entire image (default is 1)
	* the image has at least the width of the text-box
	* @param $image_width int
	* @return void
	* @access public
	*/
	function set_image_width( $image_width = 1 ) {
		$this->arr_image_params['image_width'] = sprintf( '%d', trim( $image_width ) );
		}

	/**
	* sets the size of the font
	* @param $fontsize int
	* @return void
	* @access public
	*/
	function set_fontsize( $fontsize ) {
		$this->arr_image_params['fontsize'] = sprintf( '%d', trim( $fontsize ) );
		}

	/**
	* sets the fontfile inclusive its path (relative or absolute)
	* @param $fontfile string
	* @return void
	* @access public
	*/
	function set_fontfile( $fontfile ) {
		$this->arr_image_params['fontfile'] = trim( $fontfile );
		}

	/**
	* sets the background-image-file
	* returns false if file couldn't be found
	* @param $background_image string
	* @return boolean
	* @access public
	*/
	function set_background_image( $background_image ) {
		if (is_file(trim($background_image))) {
			$this->arr_image_params['background_image'] = trim( $background_image );
			return true;
		} else {
			unset($this->arr_image_params['background_image']);
			return false;
			} // end if
		}

	/**
	* sets the background-image-file-width
	* @param $background_image string
	* @return void
	* @access public
	*/
	function set_background_image_width( $background_image_width ) {
			$this->arr_image_params['background_image_width'] = trim($background_image_width);
		}


	/**
	* sets the path where the created images will reside (relative or absolute)
	* @param $image_path string
	* @return void
	* @access public
	*/
	function set_image_path( $image_path ) {
		if ( preg_match( '|/\z|', $image_path, $array ) ) {
			$this->arr_image_params['image_path'] = trim( $image_path );
		} else {
			$this->arr_image_params['image_path'] = trim( $image_path ).'/';
			} // end if
		}

	/**
	* sets the basename of the images and
	* creates a hashed filename with the given basename, the fontfile, the fontsize, the background- and the text color and the image_type
	* @param $basename string
	* @return void
	* @access public
	*/
	function set_basename( $basename ) {
		$this->arr_image_params['basename'] = str_replace( '%', '', urlencode( trim( $basename ) ) );

		$arr_fontfile = explode( '.', basename($this->arr_image_params['fontfile']) );
		$font = urldecode( stripslashes( strip_tags( $arr_fontfile[0] ) ) );

		$this->arr_image_params['filename'] = hash('ripemd160', $this->arr_image_params['basename'].$this->arr_image_params['marginX'].$this->arr_image_params['marginY'].$this->arr_image_params['image_height'].$this->arr_image_params['image_width'].$this->arr_image_params['line_spacing'].$font.$this->arr_image_params['fontsize'].$this->arr_image_params['text_color'].$this->arr_image_params['background_color']).'.'.$this->arr_image_params['image_type'];
		}

	/**
	* sets the the type of the images (file extension)
	* default is PNG
	* @param $image_type string
	* @return void
	* @access public
	*/
	function set_image_type( $image_type = 'PNG' ) {
		switch ( strtoupper( trim( $image_type ) ) ) {
			case 'PNG':
				$this->arr_image_params['image_type'] = 'png';
			break;
			case 'GIF':
				$this->arr_image_params['image_type'] = 'gif';
			break;
			case 'JPG':
				$this->arr_image_params['image_type'] = 'jpg';
			break;
			default:
				$this->arr_image_params['image_type'] = 'png';
			}
		}

	/**
	* sets if marginY shall be calculated automatically (default is true)
	* @param $use_automatic_marginY boolean
	* @return void
	* @access public
	*/
	function set_use_automatic_marginY( $use_automatic_marginY = 1 ) {
		$this->arr_image_params['use_automatic_marginY'] = (bool)$use_automatic_marginY;
		}

	/**
	* returns the path of the last created imagefile or the image number x
	* @param $image_number
	* @return string or FALSE
	* @access public
	*/
	function get_imagefile( $image_number = '' ) {
		if ( $image_number == '' ) {
			return end( $this->arr_images );
		} else {
			$image_number = sprintf( '%d', trim( $image_number ) );
			if ( array_key_exists( $image_number, $this->arr_images ) ) {
				return $this->arr_images[$image_number];
			} else {
				return false;
				} // end if
			} // end if
		}


	/**
	* deletes the last created imagefile or the image number x
	* @param $image_number
	* @return boolean
	* @access public
	*/
	function delete_imagefile( $image = '' ) {
		$this->get_all_imagefiles();
		if ( $image == '' ) {
			$image2delete = end( $this->arr_images );
		} else {
			$image = trim( $image );
			if ( in_array( $image, $this->arr_images ) ) {
				$image2delete = $image;
			} else {
				return false;
				} // end if
			} // end if
		return unlink( $image2delete );
		}

	/**
	* deletes all images in the specified folder
	* @param void
	* @return boolean
	* @access public
	*/
	function empty_folder() {
		$this->get_all_imagefiles();
		foreach ( $this->arr_images as $key=>$image2delete ) {
				$return = unlink( $image2delete );
			} // end foreach
		return $return;
		}

	/**
	* returns an array with all images in the specified path
	* @param void
	* @return array or FALSE
	* @access public
	*/
	function get_all_imagefiles() {
		if ( is_dir( $this->arr_image_params['image_path'] ) ) {
			$dirhandle = dir( $this->arr_image_params['image_path'] );
			while ( false !== ( $direntry = $dirhandle->read() ) ) {
				if ( $direntry != '.' && $direntry != '..' && !in_array( $this->arr_image_params['image_path'].$direntry, $this->arr_images ) ) {
					$this->arr_images[] = $this->arr_image_params['image_path'].$direntry;
					} // end if
				} // end while
			$dir->close;
			if ( !empty( $this->arr_images ) ) {
				return $this->arr_images;
			} else {
				return false;
				} // end if
		} else {
			return false;
			}// end if
		}

	/**
	* returns the created filename
	* @param void
	* @return string
	* @access public
	*/
	function get_filename() {
		return $this->arr_image_params['filename'];
		}

	/**
	* this is a shortcode-alias for three methods only to simplifiy your life:
	* creates a png-image and returns its filename
	* basename is set from the given text
	* @param $text string
	* @return string
	* @access public
	*/
	function get_fontimage($text) {
		$this->create_png($this->set_text( $text, 1 ));
		return $this->get_imagefile();
		}

	/**
	* creates a png image with the given settings
	* @param void
	* @return boolean/header-png
	* @access public
	*/
	function create_png() {
		// create png-image with dynamic text if it doesn't already exist
		if ( !is_file( $this->arr_image_params['image_path'].$this->arr_image_params['filename'] ) ) {
			if( $this->arr_image_params['text'] != '' ) {
				if ( $this->arr_image_params['image_type'] == 'png' ) {
					$line = array( 'linespacing' => $this->arr_image_params['line_spacing'] );
					$text_box = @imageftbbox( $this->arr_image_params['fontsize'], 0, $this->arr_image_params['fontfile'], $this->arr_image_params['text'], $line );
						//or die("ERROR: coudln't create font-image");

					// calculate image width with the text_box dimensions
					$text_width = $text_box[4]-$text_box[0];
					if ( $this->arr_image_params['image_witdth'] > $text_width ) {
						$text_width = $this->arr_image_params['image_witdth'];
						} // end if

					if ( $this->arr_image_params['use_automatic_marginY'] == 1 ) {
						$marginY = $this->arr_image_params['image_height'] - (($this->arr_image_params['image_height'] - $this->arr_image_params['fontsize']) / 2);
					} else {
						$marginY = $this->arr_image_params['marginY'];
						} // end if

					$image_width = $text_width + ( 2*$this->arr_image_params['marginX'] );

					if (!isset($this->arr_image_params['background_image'])) {
						$im = ImageCreate( $image_width, $this->arr_image_params['image_height'] );
					} else {
						$im = imagecreatefrompng($this->arr_image_params['background_image']);

						if ($image_width > $this->arr_image_params['background_image_width']) {
							$dest_width = $image_width;
						} else {
							$dest_width = $this->arr_image_params['background_image_width'];
							} // end if
						$im = imagecreatetruecolor($dest_width,30);
						$org_img = imagecreatefrompng($this->arr_image_params['background_image']);
						$ims = getimagesize($this->arr_image_params['background_image']);
						imagecopy($im,$org_img, 0, 0, $_POST['tx'], $_POST['ty'], $ims[0], $ims[1]);
						} // end if

					$int = hexdec( $this->arr_image_params['background_color'] );
					$arr = array( 'red' => 0xFF & ( $int >> 0x10 ), 'green' => 0xFF & ( $int >> 0x8 ), 'blue' => 0xFF & $int );
					$background_color = ImageColorAllocate( $im, $arr['red'], $arr['green'], $arr['blue'] );

					$int = hexdec( $this->arr_image_params['text_color'] );
					$arr = array( 'red' => 0xFF & ( $int >> 0x10 ), 'green' => 0xFF & ( $int >> 0x8 ), 'blue' => 0xFF & $int );
					$text_color = ImageColorAllocate( $im, $arr['red'], $arr['green'], $arr['blue'] );

					ImageFtText( $im, $this->arr_image_params['fontsize'], 0, $this->arr_image_params['marginX'], $marginY, $text_color, $this->arr_image_params['fontfile'], $this->arr_image_params['text'], array() );
					if ( ImagePng( $im, $this->arr_image_params['image_path'].$this->arr_image_params['filename'] ) ) {
						$this->arr_images[] = $this->arr_image_params['image_path'].$this->arr_image_params['filename'];
						$return = true;
					} else {
						$return = false;
						} // end if
					ImageDestroy( $im );
					} // end if
			} else {
				$this->arr_image_params['text'] = 'missing dynamic image text';
				} // end if
		} else {
			$this->arr_images[] = $this->arr_image_params['image_path'].$this->arr_image_params['filename'];
			} // end if
		return $return;
		} // end function
	} // end class
?>