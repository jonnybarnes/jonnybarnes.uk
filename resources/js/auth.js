class Auth {
  constructor() {}

  async register() {
    const { challenge, userId, existing } = await this.getRegisterData();

    const publicKeyCredentialCreationOptions = {
      challenge: new TextEncoder().encode(challenge),
      rp: {
        name: 'JB',
      },
      user: {
        id: new TextEncoder().encode(userId),
        name: 'jonny@jonnybarnes.uk',
        displayName: 'Jonny',
      },
      pubKeyCredParams: [
        {alg: -8, type: 'public-key'}, // Ed25519
        {alg: -7, type: 'public-key'}, // ES256
        {alg: -257, type: 'public-key'}, // RS256
      ],
      excludeCredentials: existing,
      authenticatorSelection: {
        userVerification: 'preferred',
        residentKey: 'required',
      },
      timeout: 60000,
    };

    const publicKeyCredential = await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions
    });
    if (!publicKeyCredential) {
      throw new Error('Error generating a passkey');
    }
    const {
      id // the key id a.k.a. kid
    } = publicKeyCredential;
    const publicKey = publicKeyCredential.response.getPublicKey();
    const transports = publicKeyCredential.response.getTransports();
    const response = publicKeyCredential.response;
    const clientJSONArrayBuffer = response.clientDataJSON;
    const clientJSON = JSON.parse(new TextDecoder().decode(clientJSONArrayBuffer));
    const clientChallenge = clientJSON.challenge;
    // base64 decode the challenge
    const clientChallengeDecoded = atob(clientChallenge);

    const saved = await this.savePasskey(id, publicKey, transports, clientChallengeDecoded);

    if (saved) {
      window.location.reload();
    } else {
      alert('There was an error saving the passkey');
    }
  }

  async getRegisterData() {
    const response = await fetch('/admin/passkeys/init');

    return await response.json();
  }

  async savePasskey(id, publicKey, transports, challenge) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('transports', JSON.stringify(transports));
    formData.append('challenge', challenge);

    // Convert the ArrayBuffer to a Uint8Array
    const publicKeyArray = new Uint8Array(publicKey);

    // Create a Blob from the Uint8Array
    const publicKeyBlob = new Blob([publicKeyArray], { type: 'application/octet-stream' });

    formData.append('public_key', publicKeyBlob);

    const response = await fetch('/admin/passkeys/save', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    });

    return response.ok;
  }
}

export { Auth };
