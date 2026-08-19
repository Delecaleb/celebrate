import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { celebrations, meta as metaApi } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash, Txt } from '../../src/components/ui';
import { celebrationType, colors, radii, spacing } from '../../src/theme';

/**
 * Create a celebration — the web's create-event modal.
 *
 * Sends the same camelCase payload the web form posts (celebrantName, eventType,
 * startDate, …), so both go through identical validation.
 */
export default function CreateCelebration() {
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data: meta } = useQuery({ queryKey: ['meta'], queryFn: metaApi.get, staleTime: Infinity });

  const [celebrantName, setCelebrantName] = useState('');
  const [eventTitle, setEventTitle] = useState('');
  const [eventType, setEventType] = useState('birthday');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [errors, setErrors] = useState<Record<string, string | undefined>>({});
  const [flash, setFlash] = useState<string | null>(null);

  const create = useMutation({
    mutationFn: () =>
      celebrations.create({
        celebrantName: celebrantName.trim(),
        eventType,
        startDate: startDate.trim(),
        endDate: endDate.trim() || null,
        eventTitle: eventTitle.trim() || null,
      }),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['dashboard'] });
      // Straight to the new page, which is where you add the registry and set
      // it live — same as the web redirect.
      router.replace(`/celebration/${res.data.slug}`);
    },
    onError: (e) => {
      if (e instanceof ApiError) {
        setErrors({
          celebrantName: e.fieldError('celebrantName'),
          eventType: e.fieldError('eventType'),
          startDate: e.fieldError('startDate'),
          endDate: e.fieldError('endDate'),
        });
        if (!Object.keys(e.errors).length) setFlash(e.message);
      } else {
        setFlash('Could not create the celebration.');
      }
    },
  });

  const types = meta?.celebration_types ?? [
    { value: 'birthday', label: 'Birthday' },
    { value: 'wedding', label: 'Wedding' },
    { value: 'other', label: 'Celebration' },
  ];

  return (
    <Sheet
      title="New celebration"
      sub="Give us the basics — you can add photos, a registry and everything else on the page itself."
      footer={
        <Button
          title="Create celebration"
          full
          loading={create.isPending}
          onPress={() => {
            setErrors({});
            setFlash(null);
            create.mutate();
          }}
        />
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      <Field
        label="Who is it for?"
        value={celebrantName}
        onChangeText={setCelebrantName}
        error={errors.celebrantName}
        placeholder="Ada Lovelace"
      />

      <Txt variant="tiny" color={colors.muted} style={{ marginBottom: 8, letterSpacing: 0.4 }}>
        OCCASION
      </Txt>
      <View style={styles.typeGrid}>
        {types.map((t) => {
          const selected = eventType === t.value;

          return (
            <Pressable
              key={t.value}
              accessibilityRole="radio"
              accessibilityState={{ selected }}
              onPress={() => setEventType(t.value)}
              style={[styles.typeChip, selected && { borderColor: colors.primary, backgroundColor: colors.primaryFaint }]}
            >
              <Txt variant="small" color={selected ? colors.primary : colors.muted} style={{ fontWeight: '700' }}>
                {celebrationType(t.value).label}
              </Txt>
            </Pressable>
          );
        })}
      </View>
      {errors.eventType ? (
        <Txt variant="small" color={colors.danger} style={{ marginBottom: spacing.md }}>
          {errors.eventType}
        </Txt>
      ) : null}

      <View style={{ height: spacing.lg }} />

      <Field
        label="Start date"
        value={startDate}
        onChangeText={setStartDate}
        error={errors.startDate}
        placeholder="YYYY-MM-DD"
        hint="The day the celebration begins."
        keyboardType="numbers-and-punctuation"
      />

      <Field
        label="End date (optional)"
        value={endDate}
        onChangeText={setEndDate}
        error={errors.endDate}
        placeholder="YYYY-MM-DD"
        keyboardType="numbers-and-punctuation"
      />

      <Field
        label="Page title (optional)"
        value={eventTitle}
        onChangeText={setEventTitle}
        placeholder="Ada's 30th"
        hint="Leave blank and we'll name it for you."
      />
    </Sheet>
  );
}

const styles = StyleSheet.create({
  typeGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  typeChip: {
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.pill,
  },
});
