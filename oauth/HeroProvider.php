<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken as AccessToken;
use Psr\Http\Message\ResponseInterface as ResponseInterface;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

class HeroProvider extends AbstractProvider {

  use BearerAuthorizationTrait;

  const BASE_HERO_URL = 'https://api4.linc-ed.com';
  const BASE_HERO_DEV_URL = 'https://uk-dev-api.linc-ed.com';

  protected $devMode = false;

  public function __construct($options = [], array $collaborators = []) {
    parent::__construct($options, $collaborators);

    if (!empty($options['devMode']) && $options['devMode'] === true) {
      $this->devMode = true;
    }
  }
    public function getResourceOwnerDetailsUrl(AccessToken $token) {
        return $this->getBaseHeroUrl() . 'api/v1/me';
    }
  private function getBaseHeroUrl() {
    return $this->devMode ? static::BASE_HERO_DEV_URL : static::BASE_HERO_URL;
  }

  public function getBaseAuthorizationUrl() {
    return $this->getBaseHeroUrl() . 'oauth/authorise';
  }

  public function getBaseAccessTokenUrl(array $params) {
      return $this->devMode ? 'https://uk-dev-id.linc-ed.com/oauth/token' : 'https://id.linc-ed.com/oauth/token';

  }

  protected function getDefaultScopes() {
    return ['urn:linced:meta:service'];
  }

  protected function checkResponse(ResponseInterface $response, $data) {
    // check for errors in response and throw an exception
  }

  protected function createResourceOwner(array $response, AccessToken $token) {
    return new HeroUser($response);
  }

}