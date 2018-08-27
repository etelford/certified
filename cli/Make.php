<?php

namespace SSLGenerator;

class Make
{
	/**
	 * The CLI
	 * 
	 * @var Command
	 */
	protected $cli;

	/**
	 * Create a new instance
	 */
	public function __construct()
	{
		$this->cli = new Command();
	}

	/**
	 * Secure a domain
	 * 
	 * @param  string $domain
	 * @return void
	 */
	public function secure($domain)
	{
		$this->cleanup($domain);

		$this->makeCertificateAuthority();

		$this->createCertificate($domain);
	}

	protected function cleanup($url)
	{
		$keyPath = __DIR__ . '/../certificates/' . $url . '.key';
		$csrPath = __DIR__ . '/../certificates/' . $url . '.csr';
		$crtPath = __DIR__ . '/../certificates/' . $url . '.crt';
		$confPath = __DIR__ . '/../certificates/' . $url . '.conf';

		if (file_exists($crtPath)) {
		    unlink($confPath);
		    unlink($keyPath);
		    unlink($csrPath);
		    unlink($crtPath);
		}

		$this->cli->run(sprintf('sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain', $url));
		$this->cli->run(sprintf('sudo security delete-certificate -c "*.%s" /Library/Keychains/System.keychain', $url));
		$this->cli->run(sprintf(
		    'sudo security find-certificate -e "%s%s" -a -Z | grep SHA-1 | sudo awk \'{system("security delete-certificate -Z "$NF" /Library/Keychains/System.keychain")}\'',
		    $url, '@sslgen.local'
		));
	}

	/**
	 * Make the CA
	 * 
	 * @return void
	 */
	protected function makeCertificateAuthority()
	{
		$oName = 'SSLGenerator CA Self Signed Organization';
		$cName = 'SSLGenerator CA Self Signed CN';

		$caKeyPath = __DIR__ . '/../ca/SSLGeneratorCASelfSigned.key';
		$caPemPath = __DIR__ . '/../ca/SSLGeneratorCASelfSigned.pem';

		if (file_exists($caKeyPath)) {
			unlink($caKeyPath);
		}

		if (file_exists($caPemPath)) {
			unlink($caPemPath);
		}

		$command = (sprintf(
		    'sudo security delete-certificate -c "%s" /Library/Keychains/System.keychain',
		    $cName
		));

		$this->cli->run($command);

		$command = sprintf(
			'openssl req -new -newkey rsa:2048 -days 730 -nodes -x509 -subj "/C=/ST=/O=%s/localityName=/commonName=%s/organizationalUnitName=Developers/emailAddress=%s/" -keyout %s -out %s',
            $oName, $cName, 'rootcertificate@sslgen.local', $caKeyPath, $caPemPath
		);

		$this->cli->run($command);

		$this->trustCertificateAuthority($caPemPath);
	}

	/**
	 * Trust the CA
	 * 
	 * @param  string $caPemPath
	 * @return void
	 */
	protected function trustCertificateAuthority($caPemPath)
	{
		$this->cli->run(sprintf(
		    'sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain %s', $caPemPath
		));
	}

	/**
	 * Create and trust the cert
	 *
	 * @param  string  $url
	 * @return void
	 */
	function createCertificate($url)
	{
	    $caKeyPath = __DIR__ . '/../ca/SSLGeneratorCASelfSigned.key';
	    $caPemPath = __DIR__ . '/../ca/SSLGeneratorCASelfSigned.pem';
	    $caSrlPath = __DIR__ . '/../ca/SSLGeneratorCASelfSigned.srl';

	    $keyPath = __DIR__ . '/../certificates/' . $url . '.key';
	    $csrPath = __DIR__ . '/../certificates/' . $url . '.csr';
	    $crtPath = __DIR__ . '/../certificates/' . $url . '.crt';
	    $confPath = __DIR__ . '/../certificates/' . $url . '.conf';

	    // Make the conf file for the cert
	    $config = str_replace('MY_DOMAIN', $url, $file = file_get_contents(__DIR__ . '/../stubs/openssl.conf'));
	    file_put_contents($confPath, $config);

	    // Make the private key
	    $this->cli->run(sprintf('openssl genrsa -out %s 2048', $keyPath));

	    // Make the signing request
	    $this->cli->run(sprintf(
	        'openssl req -new -key %s -out %s -subj "/C=/ST=/O=/localityName=/commonName=%s/organizationalUnitName=/emailAddress=%s%s/" -config %s',
	        $keyPath, $csrPath, $url, $url, '@sslgen.local', $confPath
	    ));

	    // Add the SRL param
	    $caSrlParam = ' -CAcreateserial';
	    if (file_exists($caSrlPath)) {
	        $caSrlParam = ' -CAserial ' . $caSrlPath;
	    }

	    $cmd = sprintf(
	        'openssl x509 -req -sha256 -days 730 -CA %s -CAkey %s%s -in %s -out %s -extensions v3_req -extfile %s',
	        $caPemPath, $caKeyPath, $caSrlParam, $csrPath, $crtPath, $confPath
	    );

	    $this->cli->run(sprintf(
	        'openssl x509 -req -sha256 -days 730 -CA %s -CAkey %s%s -in %s -out %s -extensions v3_req -extfile %s',
	        $caPemPath, $caKeyPath, $caSrlParam, $csrPath, $crtPath, $confPath
	    ));

	    // Trust the certificate
	    $this->cli->run(sprintf(
	        'sudo security add-trusted-cert -d -r trustAsRoot -k /Library/Keychains/System.keychain %s', $crtPath
	    ));
	}
}