import { useConfirmationDialog } from '@/components/confirmation-dialog';
import { useToast } from '@/hooks/use-toast';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ModalToastTest() {
    const { error, success } = useToast();
    const { openDialog, closeDialog, ConfirmationDialog } = useConfirmationDialog();

    const testModalSequence = () => {
        console.log('🧪 Starting modal sequence test...');

        // First modal
        setTimeout(() => {
            console.log('📝 Opening first modal...');
            openDialog({
                title: 'Delete First Item',
                description: 'Are you sure you want to delete the first item?',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                variant: 'destructive',
                onConfirm: () => {
                    console.log('✅ First modal confirmed');
                    error('Cannot delete first item - it is assigned to users.');
                    closeDialog();
                },
            });
        }, 500);

        // Second modal (after first is closed)
        setTimeout(() => {
            console.log('📝 Opening second modal...');
            openDialog({
                title: 'Delete Second Item',
                description: 'Are you sure you want to delete the second item?',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                variant: 'destructive',
                onConfirm: () => {
                    console.log('✅ Second modal confirmed');
                    error('Cannot delete second item - it is assigned to users.');
                    closeDialog();
                },
            });
        }, 3000);
    };

    const testManualSequence = (itemNumber: number) => {
        console.log(`🧪 Opening modal for item ${itemNumber}...`);

        openDialog({
            title: `Delete Item ${itemNumber}`,
            description: `Are you sure you want to delete item ${itemNumber}?`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'destructive',
            onConfirm: () => {
                console.log(`✅ Item ${itemNumber} modal confirmed`);
                error(`Cannot delete item ${itemNumber} - it is assigned to users.`);
                closeDialog();
            },
        });
    };

    const testSuccessToast = () => {
        success('This is a success toast to verify toast system works');
    };

    return (
        <AppLayout>
            <Head title="Modal Toast Test" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 className="mb-6 text-2xl font-bold">Modal Toast Integration Test</h1>

                            <div className="space-y-6">
                                <div>
                                    <h2 className="mb-2 text-lg font-semibold">Automated Sequence Test</h2>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        This will automatically open two modals in sequence. Both should show error toasts when confirmed.
                                    </p>
                                    <button
                                        onClick={testModalSequence}
                                        className="rounded bg-purple-500 px-4 py-2 font-bold text-white hover:bg-purple-700"
                                    >
                                        Start Automated Test
                                    </button>
                                </div>

                                <div>
                                    <h2 className="mb-2 text-lg font-semibold">Manual Modal Tests</h2>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        Click these buttons to manually test modal behavior. Each should show an error toast when confirmed.
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        <button
                                            onClick={() => testManualSequence(1)}
                                            className="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                                        >
                                            Delete Item 1
                                        </button>
                                        <button
                                            onClick={() => testManualSequence(2)}
                                            className="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                                        >
                                            Delete Item 2
                                        </button>
                                        <button
                                            onClick={() => testManualSequence(3)}
                                            className="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                                        >
                                            Delete Item 3
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <h2 className="mb-2 text-lg font-semibold">Toast System Verification</h2>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        Test that the toast system itself is working correctly.
                                    </p>
                                    <button
                                        onClick={testSuccessToast}
                                        className="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700"
                                    >
                                        Test Success Toast
                                    </button>
                                </div>

                                <div className="mt-8 rounded-lg bg-gray-100 p-4 dark:bg-gray-700">
                                    <h3 className="mb-2 font-semibold">Expected Behavior:</h3>
                                    <ul className="space-y-1 text-sm">
                                        <li>✅ Each modal confirmation should show an error toast</li>
                                        <li>✅ Toasts should appear regardless of modal sequence</li>
                                        <li>✅ Console logs should show all confirmations</li>
                                        <li>❌ If second+ modals don't show toasts, the bug is reproduced</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmationDialog />
        </AppLayout>
    );
}
