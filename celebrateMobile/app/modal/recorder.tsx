import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CameraView, useCameraPermissions, useMicrophonePermissions } from 'expo-camera';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useVideoPlayer, VideoView } from 'expo-video';
import { useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError } from '../../src/api/client';
import { comments } from '../../src/api/endpoints';
import { Button, Txt } from '../../src/components/ui';
import { colors, radii, spacing } from '../../src/theme';

/** The web recorder caps a wish video at 60s; the API rejects over 20MB. */
const MAX_SECONDS = 60;

/**
 * Record a video wish — the web's recorder modal, done natively.
 *
 * Three states: asking for permission, recording, then reviewing what you shot
 * before it is posted. Nothing uploads until you tap Post.
 */
export default function Recorder() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const params = useLocalSearchParams<{ slug: string; id: string }>();
  const insets = useSafeAreaInsets();

  const [camPermission, requestCam] = useCameraPermissions();
  const [micPermission, requestMic] = useMicrophonePermissions();

  const cameraRef = useRef<CameraView>(null);
  const [facing, setFacing] = useState<'front' | 'back'>('front');
  const [recording, setRecording] = useState(false);
  const [elapsed, setElapsed] = useState(0);
  const [clipUri, setClipUri] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // The countdown that drives the timer and the automatic stop.
  useEffect(() => {
    if (!recording) return;

    const timer = setInterval(() => setElapsed((s) => s + 1), 1000);

    return () => clearInterval(timer);
  }, [recording]);

  useEffect(() => {
    if (recording && elapsed >= MAX_SECONDS) {
      void stop();
    }
  }, [elapsed, recording]);

  const post = useMutation({
    mutationFn: () =>
      comments.create({
        celebration_id: Number(params.id),
        videoUri: clipUri!,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['celebration', params.slug] });
      router.back();
    },
    onError: (e) => setError(e instanceof ApiError ? e.message : 'Could not post your video wish.'),
  });

  async function start() {
    setError(null);
    setElapsed(0);
    setRecording(true);

    try {
      // Resolves only when recording stops, so the result is the finished clip.
      const clip = await cameraRef.current?.recordAsync({ maxDuration: MAX_SECONDS });

      if (clip?.uri) setClipUri(clip.uri);
    } catch {
      setError('Recording failed. Please try again.');
    } finally {
      setRecording(false);
    }
  }

  async function stop() {
    cameraRef.current?.stopRecording();
    setRecording(false);
  }

  // ── Permissions ──
  if (!camPermission?.granted || !micPermission?.granted) {
    return (
      <View style={[styles.gate, { paddingTop: insets.top + spacing.xl }]}>
        <MaterialCommunityIcons name="video-outline" size={44} color={colors.primary} />
        <Txt variant="h2" center style={{ marginTop: spacing.lg }}>
          Record a video wish
        </Txt>
        <Txt variant="small" color={colors.muted} center style={{ marginTop: spacing.md, lineHeight: 21 }}>
          We need the camera and microphone to record. Nothing is uploaded until you tap Post.
        </Txt>

        <Button
          title="Allow camera & mic"
          style={{ marginTop: spacing.xl }}
          onPress={async () => {
            await requestCam();
            await requestMic();
          }}
        />
        <Button title="Cancel" variant="quiet" style={{ marginTop: spacing.sm }} onPress={() => router.back()} />
      </View>
    );
  }

  // ── Review what was recorded ──
  if (clipUri) {
    return (
      <View style={[styles.review, { paddingTop: insets.top + spacing.md, paddingBottom: insets.bottom + spacing.lg }]}>
        <Txt variant="h3" color={colors.white} style={{ marginBottom: spacing.md }}>
          Happy with it?
        </Txt>

        <ClipPlayer uri={clipUri} />

        {error ? (
          <Txt variant="small" color={colors.white} style={{ marginTop: spacing.md }}>
            {error}
          </Txt>
        ) : null}

        <View style={{ gap: spacing.sm, marginTop: spacing.lg }}>
          <Button title="Post this wish" full loading={post.isPending} onPress={() => post.mutate()} />
          <Button
            title="Record again"
            variant="quiet"
            full
            onPress={() => {
              setClipUri(null);
              setElapsed(0);
            }}
          />
        </View>
      </View>
    );
  }

  // ── Recording ──
  return (
    <View style={styles.root}>
      <CameraView ref={cameraRef} style={StyleSheet.absoluteFill} facing={facing} mode="video" />

      <View style={[styles.topRow, { paddingTop: insets.top + spacing.md }]}>
        <Pressable accessibilityLabel="Cancel" onPress={() => router.back()} style={styles.round}>
          <MaterialCommunityIcons name="close" size={20} color={colors.white} />
        </Pressable>

        {recording ? (
          <View style={styles.timer}>
            <View style={styles.recDot} />
            <Txt variant="tiny" color={colors.white}>
              {String(Math.floor(elapsed / 60)).padStart(2, '0')}:{String(elapsed % 60).padStart(2, '0')} /{' '}
              {MAX_SECONDS}s
            </Txt>
          </View>
        ) : (
          <View />
        )}

        <Pressable
          accessibilityLabel="Switch camera"
          onPress={() => setFacing((f) => (f === 'front' ? 'back' : 'front'))}
          style={styles.round}
          disabled={recording}
        >
          <MaterialCommunityIcons name="camera-flip-outline" size={20} color={colors.white} />
        </Pressable>
      </View>

      <View style={[styles.shutterRow, { paddingBottom: insets.bottom + spacing.xl }]}>
        {error ? (
          <Txt variant="small" color={colors.white} center style={{ marginBottom: spacing.md }}>
            {error}
          </Txt>
        ) : null}

        <Pressable
          accessibilityRole="button"
          accessibilityLabel={recording ? 'Stop recording' : 'Start recording'}
          onPress={recording ? stop : start}
          style={[styles.shutter, recording && { backgroundColor: colors.white }]}
        >
          {recording ? <View style={styles.stopSquare} /> : <View style={styles.recCircle} />}
        </Pressable>

        <Txt variant="small" color={colors.onDark} center style={{ marginTop: spacing.md }}>
          {recording ? 'Tap to finish' : `Tap to record — up to ${MAX_SECONDS} seconds`}
        </Txt>
      </View>
    </View>
  );
}

function ClipPlayer({ uri }: { uri: string }) {
  const player = useVideoPlayer(uri, (p) => {
    p.loop = true;
    p.play();
  });

  return <VideoView player={player} style={styles.clip} nativeControls contentFit="contain" />;
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: '#000',
  },
  gate: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
    backgroundColor: colors.surface,
  },
  review: {
    flex: 1,
    padding: spacing.lg,
    backgroundColor: '#000',
    justifyContent: 'center',
  },
  clip: {
    width: '100%',
    height: 380,
    borderRadius: radii.soft.sm,
    backgroundColor: '#111',
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
  },
  round: {
    width: 38,
    height: 38,
    borderRadius: radii.pill,
    backgroundColor: 'rgba(0,0,0,0.45)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  timer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radii.pill,
    backgroundColor: 'rgba(0,0,0,0.5)',
  },
  recDot: {
    width: 8,
    height: 8,
    borderRadius: radii.pill,
    backgroundColor: '#ff3b30',
  },
  shutterRow: {
    marginTop: 'auto',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
  },
  shutter: {
    width: 72,
    height: 72,
    borderRadius: radii.pill,
    borderWidth: 4,
    borderColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
  },
  recCircle: {
    width: 54,
    height: 54,
    borderRadius: radii.pill,
    backgroundColor: '#ff3b30',
  },
  stopSquare: {
    width: 26,
    height: 26,
    borderRadius: 4,
    backgroundColor: '#ff3b30',
  },
});
