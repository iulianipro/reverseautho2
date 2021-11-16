public function getToken(Request $request)
 {
     if (isset($this->session->token)) {
         return true;
     } elseif (strlen($this->session->state) > 0 and $this->session->state == $request->getQuery('state') and strlen($request->getQuery('code')) > 5) {
         $client = $this->getHttpClient();
         $client->setUri($this->options->getTokenUri());
         $client->setMethod(Request::METHOD_POST);
         $client->setParameterPost(array('code' => $request->getQuery('code'), 'client_id' => $this->options->getClientId(), 'client_secret' => $this->options->getClientSecret(), 'redirect_uri' => $this->options->getRedirectUri(), 'grant_type' => 'authorization_code'));
         $resBody = $client->send()->getBody();
         try {
             $response = JsonDecoder::decode($resBody, Json::TYPE_ARRAY);
             if (is_array($response) and isset($response['access_token']) and !isset($response['expires']) || $response['expires'] > 0) {
                 $this->session->token = (object) $response;
                 return true;
             } else {
                 $this->error = array('internal-error' => 'Instagram settings error.', 'message' => $response->error_message, 'type' => $response->error_type, 'code' => $response->code);
                 return false;
             }
         } catch (\Zend\Json\Exception\RuntimeException $e) {
             $this->error = array('internal-error' => 'Parse error.', 'message' => $e->getMessage(), 'code' => $e->getCode());
             return false;
         }
     } else {
         $this->error = array('internal-error' => 'State error, request variables do not match the session variables.', 'session-state' => $this->session->state, 'request-state' => $request->getQuery('state'), 'code' => $request->getQuery('code'));
         return false;
     }
 }
