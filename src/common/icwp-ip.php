<?php
if ( !class_exists( 'ICWP_Maid_Ip', false ) ):

	/**
	 * This is taken straight out of https://github.com/symfony/HttpFoundation/blob/master/IpUtils.php
	 */
	class ICWP_Maid_Ip {

		const IpifyEndpoint = 'https://api.ipify.org';

		/**
		 * @var ICWP_Maid_Ip
		 */
		protected static $oInstance = NULL;

		/**
		 * @return ICWP_Maid_Ip
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		/**
		 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
		 *
		 * @param string       $requestIp IP to check
		 * @param string|array $ips       List of IPs or subnets (can be a string if only a single one)
		 *
		 * @return bool Whether the IP is valid
		 * @throws Exception When IPV6 support is not enabled
		 */
		public static function checkIp($requestIp, $ips)
		{
			if (!is_array($ips)) {
				$ips = array($ips);
			}
			$method = substr_count($requestIp, ':') > 1 ? 'checkIp6' : 'checkIp4';
			foreach ($ips as $ip) {
				if (self::$method($requestIp, $ip)) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Compares two IPv4 addresses.
		 * In case a subnet is given, it checks if it contains the request IP.
		 *
		 * @param string $requestIp IPv4 address to check
		 * @param string $ip        IPv4 address or subnet in CIDR notation
		 *
		 * @return bool Whether the IP is valid
		 */
		public static function checkIp4($requestIp, $ip)
		{
			if (false !== strpos($ip, '/')) {
				if ('0.0.0.0/0' === $ip) {
					return true;
				}
				list($address, $netmask) = explode('/', $ip, 2);
				if ($netmask < 1 || $netmask > 32) {
					return false;
				}
			} else {
				$address = $ip;
				$netmask = 32;
			}
			return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
		}

		/**
		 * Compares two IPv6 addresses.
		 * In case a subnet is given, it checks if it contains the request IP.
		 *
		 * @author David Soria Parra <dsp at php dot net>
		 *
		 * @see https://github.com/dsp/v6tools
		 *
		 * @param string $requestIp IPv6 address to check
		 * @param string $ip        IPv6 address or subnet in CIDR notation
		 *
		 * @return bool Whether the IP is valid
		 *
		 * @throws Exception When IPV6 support is not enabled
		 */
		public static function checkIp6($requestIp, $ip) {
			if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
				throw new Exception('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
			}
			if (false !== strpos($ip, '/')) {
				list($address, $netmask) = explode('/', $ip, 2);
				if ($netmask < 1 || $netmask > 128) {
					return false;
				}
			} else {
				$address = $ip;
				$netmask = 128;
			}
			$bytesAddr = unpack('n*', inet_pton($address));
			$bytesTest = unpack('n*', inet_pton($requestIp));
			for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
				$left = $netmask - 16 * ($i - 1);
				$left = ($left <= 16) ? $left : 16;
				$mask = ~(0xffff >> $left) & 0xffff;
				if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Assumes a valid IPv4 address is provided as we're only testing for a whether the IP is public or not.
		 *
		 * @param string $sIpAddress
		 * @return boolean
		 */
		public function isAddressInPublicIpRange( $sIpAddress ) {
			return filter_var( $sIpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE );
		}

		/**
		 * @param string $sIp
		 * @param bool $bOnlyPublicRemotes
		 * @return boolean
		 */
		public function isValidIp( $sIp, $bOnlyPublicRemotes = false ) {
			$flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
			if ( $bOnlyPublicRemotes ) {
				$flags = $flags | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
			}
			return filter_var( $sIp, FILTER_VALIDATE_IP, $flags );
		}

		/**
		 * @param string $sIp
		 * @return boolean
		 */
		public function isValidIpRange( $sIp ) {
			if ( strpos( $sIp, '/' ) == false ) {
				return false;
			}
			$aParts = explode( '/', $sIp );
			return filter_var( $aParts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && ( 0 < $aParts[1] && $aParts[1] < 33 );
		}

		/**
		 * @return string|false
		 */
		public static function WhatIsMyIp() {

			$sIp = '';
			if ( class_exists( 'ICWP_Maid_WpFilesystem' ) ) {
				$oWpFs = ICWP_Maid_WpFilesystem::GetInstance();
				$sIp = $oWpFs->getUrlContent( self::IpifyEndpoint );
				if ( empty( $sIp ) || !is_string( $sIp ) ) {
					$sIp = '';
				}
			}
			return $sIp;
		}
	}
endif;