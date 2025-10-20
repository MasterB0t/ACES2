<?php 

$ERRORS['iptv_device_name_empty']					= 'Account name is needed.';
$ERRORS['iptv_device_name_short']					= 'Account name is too short.';
$ERRORS['iptv_device_name_long']					= 'Account name is too long,';
$ERRORS['iptv_device_name_illegal']                                     = 'Account name is wrong.';

$ERRORS['iptv_device_subscription_empty']                               = 'Enter a date for expiration.';
$ERRORS['iptv_device_subscription_illegal']                             = 'Enter a valid date for expiration date.';

$ERRORS['iptv_device_no_credits']					= 'You do not have enough credits.';
$ERRORS['iptv_device_no_premium_credits']                               = 'You do not have enough premium credits.';
$ERRORS['iptv_device_max_demos']					= 'You cannot add more demo Account.';
$ERRORS['iptv_device_no_credits_for_demos']                             = 'You need at least one credit on this bouquets to create demo.';
$ERRORS['iptv_device_package_empty']                                    = 'Please Select a Package.';
$ERRORS['iptv_device_package_illegal']                                  = 'The bouquets you selected do not exist.';


$ERRORS['iptv_device_credits_empty']                                    = 'Please select credits to use.';
$ERRORS['iptv_device_credits_illegal']                                  = 'Credits must be numeric value.';
$ERRORS['iptv_device_empty'] 						= 'Could not found account.';
$ERRORS['iptv_device_renew_no_demo']                                    = 'You can only renew demo account. Use edit instead.';
$ERRORS['iptv_device_demo_credits']					= 'You can not add credits to a demo account.';
$ERRORS['iptv_device_no_credits']					= 'You do not have enough credits of this bouquet.';

$ERRORS['iptv_device_token_empty']					= 'Please enter a token.';
$ERRORS['iptv_device_token_illegal']                                    = 'Illegal characters on token.';
$ERRORS['iptv_device_token_short']					= 'The token is too short.';
$ERRORS['iptv_device_token_long']					= 'The token is too long.';

$ERRORS['iptv_device_mag_illegal']                                      = 'Please enter a valid mac address.';
$ERRORS['iptv_device_mag_duplicated']                                   = 'There is another account with this mac.';

//$ERRORS['iptv_device_package_empty']                                    = 'Please select a bouquet for the device.';
$ERRORS['iptv_device_bouquet_illegal']                                  = 'One or more of the selected bouquet do not exist.';

$ERRORS['iptv_device_lock_ip_address']                                  = 'Please enter a valid ip-address.';
$ERRORS['iptv_device_limit_connections_illegal']                        = 'Limit connections must be a numeric value.';
$ERRORS['iptv_device_owner_not_found']                                  = 'The user selected can not be found on database.';
$ERRORS['iptv_device_pin_illegal']					= 'Account pin must be a numeric value with four digits.';
$ERRORS['iptv_device_not_found'] 					= 'The account selected could not be found.';

$ERRORS['iptv_device_security_level_illegal']                           = 'Please enter a valid security monitor.';

$ERRORS['iptv_stream_source_id_illegal']                                = 'Wrong stream source.';
$ERRORS['iptv_stream_source_name_illegal']                              = 'Wrong stream source name.';
$ERRPRS['iptv_stream_source_priority_illlegal']                         = 'The priority is wrong.';
$ERRORS['iptv_stream_source_channel_empty']                             = '';
$ERRORS['iptv_stream_source_url_empty']                                 = 'The url is needed.';

$ERRORS['iptv_channel_name_empty'] 					= 'The channel name is needed.';
$ERRORS['iptv_channel_name_illegal']                                    = 'The channel name is wrong.';
$ERRORS['iptv_channel_exist']                                   	= 'The channel already exist.';
$ERRORS['iptv_channel_category_empty']                                  = 'Select a category.';
$ERRORS['iptv_channel_category_illegal']                                = 'The channel category is wrong.';
$ERRORS['iptv_channel_category_no_exist']                               = 'The channel category do not exist.';
$ERRORS['iptv_channel_quality_empty']                                   = 'Select a channel quality.';
$ERRORS['iptv_channel_quality_illegal']                                 = 'The channel quality is wrong.';
$ERRORS['iptv_channel_stream_profile_empty']                            = 'Please select a stream profile.';
$ERRORS['iptv_channel_stream_profile_not_exist']			= 'The stream profile selected could not be found on database.';
$ERRORS['iptv_channel_stream_server_empty']				= 'Please select a stream server.';
$ERRORS['iptv_channel_stream_server_not_exist']				= 'The stream server selected could not be found on database.';

$ERRORS['iptv_stream_option_name_empty']				= 'Please select a name.';
$ERRORS['iptv_stream_option_name_illegal']				= 'The name is wrong.';
$ERRORS['iptv_stream_option_name_exist']				= 'The name is in use.';
$ERRORS['iptv_stream_option_segment_time_empty']                        = 'Please select a time for each segments';
$ERRORS['iptv_stream_option_segment_time_illegal']			= 'Value for segment time must be numeric.';
$ERRORS['iptv_stream_option_segment_list_files_empty']			= 'Please select a number to place segments on playlist.';
$ERRORS['iptv_stream_option_segment_list_files_illegal']		= 'Value for segment list files must be numeric.';
$ERRORS['iptv_stream_option_segment_wrap_empty']			= 'Please select a number to wrap segments';
$ERRORS['iptv_stream_option_segment_wrap_illegal']			= 'Value for segment wrap must be numeric.';
$ERRORS['iptv_stream_vcodec_unknown']                                   = 'Unknown video codec.';
$ERRORS['iptv_stream_acodec_unknown']					= 'Unknown audio codec.';
$ERRORS['iptv_stream_profile_video_bitrate_illegal']                    = 'Please enter a valid video bitrate';
$ERRORS['iptv_stream_profile_audio_bitrate_illegal']                    = 'Please enter a valid audio bitrate';
$ERRORS['iptv_stream_profile_screen_size_illegal']                      = 'Please enter a valid screen size.';
$ERRORS['iptv_stream_profile_framerate_illegal']                        = 'Please enter a valid framerate value.';
$ERRORS['iptv_stream_profile_threads_illegal']                          = 'Please enter a valid threads value.';
$ERRORS['iptv_stream_profile_preset_illegal']                           = 'Please select a valid preset.';

$ERRORS['iptv_stream_not_for_stream']					= 'Can not start this stream it is not set for streaming.';
$ERRORS['iptv_stream_not_sources']					= 'Can not start this stream have no sources.';

$ERRORS['iptv_user_credits_illegal']					= 'Credits must be a numeric value.';
$ERRORS['iptv_user_package_credits_illegal']                            = 'Package credits must be a numeric value.';
$ERRORS['iptv_user_credits_not_exist']					= 'Credit you select have been delete.';
$ERRORS['iptv_user_demos_illegal']					= 'Demos must be a numeric value.';


$ERRORS['iptv_stream_server_timeout']					= 'Could not communicate with stream server.';
$ERRORS['iptv_server_no_lic']                                           = "No license found on this server or it's expired.";

$ERRORS['iptv_bouquet_name_empty']					= 'Enter a bouquet name.';
$ERRORS['iptv_bouquet_name_illegal'] 					= 'Bouquet name contain illegal characters.';
$ERRORS['iptv_bouquet_exist']						= 'A bouquet with this name already exist.';
$ERRORS['iptv_bouquet_no_exist']					= 'The bouquet selected do not exist.';
$ERRORS['iptv_device_bouquet_without_credits']                          = 'In order to change package you must add at least one credit.';

$ERRORS['iptv_bouquet_stream_illegal']					= 'Illegal stream set or stream have been deleted.';
$ERRORS['iptv_bouquet_stream_empty']					= 'At least one stream must be in package.';

$ERRORS['iptv_bouquet_package_illegal']                                 = 'Please select a package bouquet.';
$ERRORS['iptv_bouquet_package_not_exist']                               = 'The package bouquet selected do not exist.';
$ERRORS['iptv_bouquet_package_name_empty']                              = 'Please enter a name for the package bouquet.';
$ERRORS['iptv_bouquet_package_name_illegal']                            = 'Package name contain illegal characters.';
$ERRORS['iptv_bouquet_package_name_exist']                              = 'This package name already exist.';


$ERRORS['iptv_stream_category_name_empty']				= 'Please enter a name for the category.';
$ERRORS['iptv_stream_category_name_illegal']                            = 'Category name have illegal caracters.';
$ERRORS['iptv_stream_category_name_exist']				= 'This category already exist.';


$ERRORS['iptv_vod_name_empty']                                          = 'Please enter a name for the Video.';
$ERRORS['iptv_vod_exist']						= 'This name is already in use please select another one.';
$ERRORS['iptv_vod_file_empty']						= 'Please select a source for video.';
$ERRORS['iptv_vod_file_no_exist']					= 'This video file do not exist.';
$ERRORS['iptv_vod_logo_not_supported']					= 'The image selected for logo is not supported.';
$ERRORS['iptv_vod_upfile_size_limit']					= 'You have exceed the size limit of upload.';
$ERRORS['iptv_vod_file_not_supported']					= 'This file format is not supported.';
$ERRORS['iptv_vod_year_invalid']					= 'Please enter a valid year.';
$ERRORS['iptv_vod_rating_invalid']                                      = 'Please enter a valid rating value.';
$ERRORS['iptv_vod_type_empty']						= 'Please enter a type.';
$ERRORS['iptv_vod_type_invalid']                                        = 'Please enter a valid type.';
$ERRORS['iptv_vod_not_exist']                                           = 'Please select a vod.';


$ERRORS['iptv_vod_upfile_empty']					= 'Please enter a file for upload.';
$ERRORS['iptv_vod_upfile_size_limit']					= 'You exceed the size limit of upload.';
$ERRORS['iptv_vod_upfile_format_not_supported']                         = 'This file format is not supported.';

$ERRORS['iptv_vod_server_not_exist']                                    = 'The server selected do not exist in database.';
$ERRORS['iptv_vod_server_empty']                                        = 'Please select a server.';
$ERRORS['iptv_vod_move_same_server']                                    = 'Cannot move video to same server.';
$ERRORS['iptv_vod_move_not_complete']                                   = "It's not posible to move or re encode video that are being processing.";

$ERRORS['iptv_epg_source_name_empty']                                   = 'Please enter a name for the epg source.';
$ERRORS['iptv_epg_source_name_illegal']                                 = 'You have illegal character on epg source name.';
$ERRORS['iptv_epg_source_url_illegal']                                  = 'Please enter a valid url address.';
$ERRORS['iptv_epg_source_url_illegal_format']                           = 'This url do not contain a XMLTV file.';

$ERRORS['iptv_series_season_number_illegal']                            = 'Please use a numeric value for season number.';
$ERRORS['iptv_series_season_number_exist']                              = 'This season number already been added in this series.';

$ERRORS['iptv_series_episode_number_illegal']                           = 'Please use a numeric value for episode number.';
$ERRORS['iptv_series_episode_number_exist']                             = 'This episode number already exist in this season.';
$ERRORS['iptv_series_episode_title_empty']                              = 'Please enter episode title.';
$ERRORS['iptv_series_episode_title_illegal']                            = 'Please enter a valid title for episode.';