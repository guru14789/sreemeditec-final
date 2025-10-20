<?php
namespace Models;

require_once __DIR__ . '/../config/firebase.php';

class Contact
{
    private $firestore;
    private $contactsCollection;

    public function __construct()
    {
        $this->firestore = \get_firebase_firestore();
        $this->contactsCollection = $this->firestore->collection('contacts');
    }

    public function create(array $contactData): array
    {
        $requiredFields = ['name', 'email', 'message'];
        $errors = \validateRequiredFields($contactData, $requiredFields);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $contactId = $this->firestore->collection('contacts')->newDocument()->id();
            
            $data = [
                'id' => $contactId,
                'name' => \sanitizeInput($contactData['name']),
                'email' => \sanitizeInput($contactData['email']),
                'phone' => \sanitizeInput($contactData['phone'] ?? ''),
                'company' => \sanitizeInput($contactData['company'] ?? ''),
                'service' => \sanitizeInput($contactData['service'] ?? ''),
                'message' => \sanitizeInput($contactData['message']),
                'status' => 'new',
                'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTime()),
                'userId' => $contactData['userId'] ?? null
            ];

            $this->contactsCollection->document($contactId)->set($data);

            return [
                'success' => true,
                'message' => 'Contact message sent successfully',
                'contactId' => $contactId
            ];
        } catch (\Exception $e) {
            error_log("Contact creation error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to send contact message']];
        }
    }

    public function getAll(): array
    {
        try {
            $documents = $this->contactsCollection
                ->orderBy('createdAt', 'DESC')
                ->documents();

            $contacts = [];
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $contacts[] = $document->data();
                }
            }

            return [
                'success' => true,
                'contacts' => $contacts
            ];
        } catch (\Exception $e) {
            error_log("Get contacts error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to retrieve contacts']];
        }
    }

    public function getById(string $contactId): array
    {
        try {
            $document = $this->contactsCollection->document($contactId)->snapshot();
            
            if (!$document->exists()) {
                return ['success' => false, 'errors' => ['Contact not found']];
            }

            return [
                'success' => true,
                'contact' => $document->data()
            ];
        } catch (\Exception $e) {
            error_log("Get contact error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to retrieve contact']];
        }
    }

    public function updateStatus(string $contactId, string $status): array
    {
        try {
            $this->contactsCollection->document($contactId)->update([
                ['path' => 'status', 'value' => $status],
                ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())]
            ]);

            return [
                'success' => true,
                'message' => 'Contact status updated successfully'
            ];
        } catch (\Exception $e) {
            error_log("Update contact status error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to update contact status']];
        }
    }

    public function delete(string $contactId): array
    {
        try {
            $this->contactsCollection->document($contactId)->delete();

            return [
                'success' => true,
                'message' => 'Contact deleted successfully'
            ];
        } catch (\Exception $e) {
            error_log("Delete contact error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to delete contact']];
        }
    }
}
