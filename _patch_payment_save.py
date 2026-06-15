import re
from pathlib import Path

src = Path('app/Http/Controllers/ChristeningController.php').read_text(encoding='utf-8')
m = re.search(
    r'(    public function christeningPaymentSave\(Request \$request\): JsonResponse\s*\{.*?\n    \})\n\n    public function deleteChristeningRecord',
    src,
    re.DOTALL,
)
if not m:
    raise SystemExit('christening method not found')
base = m.group(1)

configs = [
    ('WeddingController.php', 'weddingPaymentSave', 'weddingMarriageApplicationDetails', [
        ('christening_id', 'wedding_id'),
        ('christeningId', 'weddingId'),
        ('christening', 'wedding'),
        ('Christening', 'Wedding'),
        ('christeningPaymentSave', 'weddingPaymentSave'),
        ('generateUniqueChristeningReferenceCode', 'generateUniqueWeddingReferenceCode'),
        ('defaultChristeningPaymentFeeRows', 'defaultWeddingPaymentFeeRows'),
    ]),
    ('BurialController.php', 'burialPaymentSave', 'burialApplicationDetails', [
        ('christening_id', 'burial_id'),
        ('christeningId', 'burialId'),
        ('christening', 'burial'),
        ('Christening', 'Burial'),
        ('christeningPaymentSave', 'burialPaymentSave'),
        ('generateUniqueChristeningReferenceCode', 'generateUniqueBurialReferenceCode'),
        ('defaultChristeningPaymentFeeRows', 'defaultBurialPaymentFeeRows'),
    ]),
    ('ConfirmationController.php', 'confirmationPaymentSave', 'deleteConfirmationRecord', [
        ('christening_id', 'confirmation_id'),
        ('christeningId', 'confirmationId'),
        ('christening', 'confirmation'),
        ('Christening', 'Confirmation'),
        ('christeningPaymentSave', 'confirmationPaymentSave'),
        ('generateUniqueChristeningReferenceCode', 'generateUniqueConfirmationReferenceCode'),
        ('defaultChristeningPaymentFeeRows', 'defaultConfirmationPaymentFeeRows'),
    ]),
]

for file_name, method_name, next_method, pairs in configs:
    method = base
    for old, new in pairs:
        method = method.replace(old, new)
    replacement = method + '\n\n    public function ' + next_method
    path = Path('app/Http/Controllers/' + file_name)
    text = path.read_text(encoding='utf-8')
    pat = (
        r'    public function '
        + re.escape(method_name)
        + r'\(Request \$request\): JsonResponse\s*\{.*?\n    \}\n\n    public function '
        + re.escape(next_method)
    )
    new_text, n = re.subn(pat, lambda _m: replacement, text, count=1, flags=re.DOTALL)
    if n != 1:
        print('FAIL', file_name, n)
    else:
        path.write_text(new_text, encoding='utf-8')
        print('OK', file_name)
